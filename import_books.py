import pandas as pd
import mysql.connector
import re
import math
import logging
import os
from datetime import datetime

# Set up logging
logging.basicConfig(
    filename=f'import_log_{datetime.now().strftime("%Y%m%d_%H%M%S")}.txt',
    filemode='w',
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    encoding='utf-8'
)

# Add console handler for real-time output
console = logging.StreamHandler()
console.setLevel(logging.INFO)
formatter = logging.Formatter('%(asctime)s - %(levelname)s - %(message)s')
console.setFormatter(formatter)
logging.getLogger('').addHandler(console)

logging.info("Starting book import process")

# Placeholder values
PLACEHOLDER_PUBLISHER = 'No Publisher'
PLACEHOLDER_FIRST_NAME = 'No First Name'
PLACEHOLDER_MIDDLE_NAME = 'No Middle Name'
PLACEHOLDER_LAST_NAME = 'No Last Name'
PLACEHOLDER_TITLE = 'No Title'
PLACEHOLDER_ACCESSION = 'No Accession'
PLACEHOLDER_CALL_NO = 'No Call Number'
PLACEHOLDER_EDITION = None

# Database Connection
db = mysql.connector.connect(
    host="localhost",
    user="root",
    port=3306,
    password="",
    database="librarydb"
)
db.autocommit = False  # Disable autocommit to use transactions
cursor = db.cursor()

# File paths
excel_path = r"C:\xampp\htdocs\STI-DigiLibrary\LIBRARY-HOLDINGS-IT-WITH-ISBN.xlsx"
covers_dir = r"C:\xampp\htdocs\STI-DigiLibrary\covers"

# Load data
df = pd.read_excel(excel_path)

# Validate required columns
required_columns = {'TITLE', 'AUTHOR', 'PUBLISHER', 'CALL NO.', 'ACCESSION', 'ISBN', 'ED./VOL.', 'PAGES'}
missing_columns = required_columns - {str(col).strip() for col in df.columns}
if missing_columns:
    error_msg = f"Error: Missing required columns in Excel file: {', '.join(sorted(missing_columns))}"
    logging.error(error_msg)
    raise ValueError(error_msg)

# Log column names for verification
logging.info(f"Found columns: {', '.join(str(col).strip() for col in df.columns)}")

# Initialize cover detection
available_covers = {os.path.splitext(f)[0] for f in os.listdir(covers_dir) if f.endswith('.webp')} if os.path.exists(covers_dir) else set()
logging.info(f"Found {len(available_covers)} cover images in {covers_dir}")

def clean_int(val, default=0, row_index=None, field_name=None):
    """Extract integer from string, log if fallback to default.
    
    Args:
        val: The value to convert to integer
        default: Default value to return if conversion fails
        row_index: Row number for logging
        field_name: Field name for logging
        
    Returns:
        int: The extracted integer or default value
    """
    try:
        return int(val)
    except (TypeError, ValueError):
        match = re.search(r'\d+', str(val))
        if match:
            return int(match.group())
        else:
            if row_index is not None and field_name:
                logging.warning(f"Row {row_index+1} | {field_name}: '{val}' | Issue: Could not extract a number, using default {default}")
            return default

def normalize_isbn(val):
    """Normalize ISBN by removing non-alphanumeric characters and converting to uppercase.
    
    Args:
        val: The ISBN value to normalize
        
    Returns:
        str: Normalized ISBN or None if input is empty/None/not found
    """
    # Handle None, empty strings, or common placeholders
    if not val or str(val).strip().lower() in ['not found', 'none', 'nan', 'n/a', '']:
        return None
        
    # Clean and validate the ISBN
    isbn = re.sub(r'[^0-9Xx]', '', str(val)).upper()
    
    # Validate the length (ISBN-10 or ISBN-13)
    if len(isbn) not in [10, 13]:
        logging.debug(f"Invalid ISBN length for '{val}': {len(isbn)} digits")
        return None
        
    return isbn

def normalize_publisher(name, row_index=None):
    """Normalize publisher name by standardizing case and removing extra characters.
    
    Args:
        name: The publisher name to normalize
        row_index: Optional row index for logging
        
    Returns:
        str: Normalized publisher name
    """
    if not name or pd.isna(name):
        return PLACEHOLDER_PUBLISHER
        
    # Convert to string and clean up
    normalized = str(name).strip()
    
    # Remove common variations and standardize
    normalized = re.sub(r'[^\w\s]', ' ', normalized)  # Keep only alphanumeric and spaces
    normalized = re.sub(r'\s+', ' ', normalized)  # Collapse multiple spaces
    normalized = normalized.lower()  # Convert to lowercase
    
    # Handle common variations
    variations = {
        'inc': '', 'incorporated': '', 'llc': '', 'ltd': '', 'limited': '',
        'publishing': 'publish', 'company': 'co', 'corporation': 'corp'
    }
    
    for old, new in variations.items():
        normalized = re.sub(rf'\b{re.escape(old)}\b', new, normalized)
    
    # Clean up any double spaces that might have been created
    normalized = re.sub(r'\s+', ' ', normalized).strip()
    
    # Log if the name was significantly changed
    if row_index is not None and str(name).strip().lower() != normalized:
        logging.info(f"Row {row_index+1} | Publisher normalized: '{name}' -> '{normalized}'")
    
    return normalized if normalized else PLACEHOLDER_PUBLISHER

def clean_edition(val, row_index=None):
    """Clean and validate edition values.
    
    Args:
        val: The edition value to clean
        row_index: Row number for logging
        
    Returns:
        str: Cleaned edition string or None if empty/invalid
    """
    # Handle None, NaN, or empty strings
    if pd.isna(val) or (isinstance(val, str) and val.strip() == ""):
        return None
    
    # Convert to string and clean up whitespace
    cleaned = str(val).strip()
    
    # Check for common placeholders that should be treated as None
    if cleaned.lower() in ['none', 'n/a', 'na', 'not specified', '']:
        return None
        
    # Log if the value was modified during cleaning
    if row_index is not None and str(val) != cleaned:
        logging.info(f"Row {row_index+1} | Field: 'ED./VOL.' | Cleaned edition: '{val}' -> '{cleaned}'")
        
    return cleaned

def clean_value(val, placeholder=None, field_name=None, row_index=None):
    """Convert NaN/empty strings to placeholder or None.
    
    Args:
        val: The value to clean
        placeholder: The placeholder value to use if val is empty/None
        field_name: Name of the field being cleaned (for logging)
        row_index: Row number (for logging)
        
    Returns:
        The cleaned value or placeholder
    """
    # Special handling for edition field
    if field_name and field_name.upper() == 'ED./VOL.':
        return clean_edition(val, row_index)
        
    original_val = val
    if pd.isna(val) or (isinstance(val, str) and val.strip() == ""):
        if field_name and row_index is not None and placeholder != original_val:
            logging.warning(f"Row {row_index+1} | Field: '{field_name}' | Issue: Empty value replaced with placeholder: '{placeholder}'")
        return placeholder
    
    cleaned = str(val).strip() if isinstance(val, (str, int, float)) else val
    
    # Log if the value was modified during cleaning
    if field_name and row_index is not None and str(original_val) != str(cleaned):
        logging.info(f"Row {row_index+1} | Field: '{field_name}' | Cleaned value: '{original_val}' -> '{cleaned}'")
        
    return cleaned

def parse_authors(author_field, row_index=None):
    """Parse author names from a string that may contain multiple authors.
    
    Args:
        author_field (str): The author field from the CSV
        row_index (int, optional): Row number for logging
        
    Returns:
        list: List of tuples (first_name, middle_name, last_name)
    """
    if not author_field or str(author_field).strip() == '':
        if row_index is not None:
            logging.warning(f"Row {row_index+1} | Author: '' | Issue: Empty author field, using placeholder values")
        return [(PLACEHOLDER_FIRST_NAME, PLACEHOLDER_MIDDLE_NAME, PLACEHOLDER_LAST_NAME)]
        
    # Normalize the string
    author_field = str(author_field).strip()
    
    # Split multiple authors (handle 'and', '&', ';' as separators)
    authors = re.split(r'\s*(?:;|&|and|\n|\r\n?|\r)\s*', author_field, flags=re.IGNORECASE)
    authors = [a.strip() for a in authors if a.strip()]
    
    result = []
    
    for author in authors:
        if not author:
            continue
            
        # Remove extra whitespace and normalize commas
        author = re.sub(r'\s*,\s*', ', ', author)
        author = re.sub(r'\s+', ' ', author).strip()
        
        # Special handling for organization names (no comma and 1-2 words)
        if ',' not in author:
            words = author.split()
            if len(words) <= 2:
                # Treat as organization name (e.g., 'STI College')
                first = PLACEHOLDER_FIRST_NAME
                middle = PLACEHOLDER_MIDDLE_NAME
                last = author
                result.append((first, middle, last))
                if row_index is not None:
                    logging.info(f"Row {row_index+1} | Author: '{author}' | Treated as organization name")
                continue
        
        # Original parsing logic for personal names
        if ',' in author:
            # Format: Last, First [Middle] or Last, First [M.I.]
            parts = [p.strip() for p in author.split(',', 1)]
            last = parts[0] if parts[0] else PLACEHOLDER_LAST_NAME
            first_middle = parts[1] if len(parts) > 1 else PLACEHOLDER_FIRST_NAME
            
            # Split first and middle names
            fm_parts = first_middle.split()
            first = fm_parts[0] if fm_parts else PLACEHOLDER_FIRST_NAME
            middle = ' '.join(fm_parts[1:]) if len(fm_parts) > 1 else PLACEHOLDER_MIDDLE_NAME
            
        else:
            # No comma: try to split by space
            words = author.split()
            if not words:
                first, middle, last = PLACEHOLDER_FIRST_NAME, PLACEHOLDER_MIDDLE_NAME, PLACEHOLDER_LAST_NAME
            elif len(words) == 1:
                first, middle, last = PLACEHOLDER_FIRST_NAME, PLACEHOLDER_MIDDLE_NAME, words[0]
            elif len(words) == 2:
                first, middle, last = words[0], PLACEHOLDER_MIDDLE_NAME, words[1]
            else:
                # Assume last word is last name, first is first name, rest is middle
                first = words[0]
                last = words[-1]
                middle = ' '.join(words[1:-1]) if len(words) > 2 else PLACEHOLDER_MIDDLE_NAME
        
        # Clean up any remaining empty strings
        first = first or PLACEHOLDER_FIRST_NAME
        middle = middle or PLACEHOLDER_MIDDLE_NAME
        last = last or PLACEHOLDER_LAST_NAME
        
        # Handle initials (e.g., "J. R. R. Tolkien" -> "J. R. R.", "Tolkien")
        if len(first) <= 2 and first.endswith('.'):
            if middle == PLACEHOLDER_MIDDLE_NAME:
                middle = first
                first = PLACEHOLDER_FIRST_NAME
            else:
                middle = f"{first} {middle}"
                first = PLACEHOLDER_FIRST_NAME
        
        result.append((first, middle, last))
    
    return result if result else [(PLACEHOLDER_FIRST_NAME, PLACEHOLDER_MIDDLE_NAME, PLACEHOLDER_LAST_NAME)]

def get_or_create_author(first_name, middle_name, last_name, row_index=None):
    """Get or create an author with first, middle, and last names.
    
    Args:
        first_name (str): Author's first name
        middle_name (str): Author's middle name
        last_name (str): Author's last name
        row_index (int, optional): Row number for logging
        
    Returns:
        int: The author_id of the found or created author
    """
    # First try exact match on all three fields
    cursor.execute(
        """
        SELECT author_id FROM TBL_AUTHORS 
        WHERE first_name = %s AND middle_name = %s AND last_name = %s
        """,
        (first_name, middle_name, last_name)
    )
    row = cursor.fetchone()
    if row:
        if row_index is not None:
            logging.debug(f"Row {row_index+1} | Author: '{first_name} {middle_name} {last_name}' | Found existing author with ID: {row[0]}")
        return row[0]
    
    # If not found, try without middle name (in case it's not in the DB yet)
    cursor.execute(
        """
        SELECT author_id, middle_name FROM TBL_AUTHORS 
        WHERE first_name = %s AND last_name = %s
        """,
        (first_name, last_name)
    )
    row = cursor.fetchone()
    if row:
        author_id, existing_middle = row
        # Only update if middle name is different and not a placeholder
        if existing_middle != middle_name and not any(x in str(existing_middle).lower() for x in ['no middle', 'n/a', '']):
            if row_index is not None:
                logging.info(f"Row {row_index+1} | Author: '{first_name} {middle_name} {last_name}' | Updating middle name from '{existing_middle}' to '{middle_name}'")
            cursor.execute(
                """
                UPDATE TBL_AUTHORS 
                SET middle_name = %s 
                WHERE author_id = %s
                """,
                (middle_name, author_id)
            )
            db.commit()
        return author_id
    
    # If still not found, insert new author
    try:
        cursor.execute(
            """
            INSERT INTO TBL_AUTHORS (first_name, middle_name, last_name) 
            VALUES (%s, %s, %s)
            """,
            (first_name, middle_name, last_name)
        )
        db.commit()
        author_id = cursor.lastrowid
        if row_index is not None:
            logging.info(f"Row {row_index+1} | Author: '{first_name} {middle_name} {last_name}' | Created new author with ID: {author_id}")
        return author_id
    except Exception as e:
        db.rollback()
        if row_index is not None:
            logging.error(f"Row {row_index+1} | Author: '{first_name} {middle_name} {last_name}' | Error creating author: {str(e)}")
        raise

def get_or_create(table, search_col, value, insert_col='name', row_index=None, field_name=None):
    """Generic function to get or create a record in the specified table.
    
    Args:
        table (str): Name of the table
        search_col (str): Column to search for existing records
        value (str): Value to search for
        insert_col (str): Column to insert into if creating a new record
        row_index (int, optional): Row number for logging
        field_name (str, optional): Field name for logging
        
    Returns:
        int: The ID of the found or created record
    """
    if table == 'TBL_AUTHORS':
        raise ValueError("Use get_or_create_author() for author records")
        
    table_col_lookup = {
        'TBL_PUBLISHERS': ('publisher_id', 'name'),
        'TBL_BOOKS': ('book_id', 'title, publisher_id')
    }
    
    id_col, _ = table_col_lookup.get(table, (f"{table.lower()}_id", search_col))
    
    try:
        cursor.execute(f"SELECT {id_col} FROM {table} WHERE {search_col} = %s", (value,))
        row = cursor.fetchone()
        
        if row:
            if row_index is not None and field_name:
                logging.debug(f"Row {row_index+1} | {field_name}: '{value}' | Found existing {table} with ID: {row[0]}")
            return row[0]
        
        # Log creation of new record
        if row_index is not None and field_name:
            logging.info(f"Row {row_index+1} | {field_name}: '{value}' | Creating new {table} record")
            
        cursor.execute(f"INSERT INTO {table} ({insert_col}) VALUES (%s)", (value,))
        db.commit()
        return cursor.lastrowid
    except Exception as e:
        db.rollback()
        if row_index is not None and field_name:
            logging.error(f"Row {row_index+1} | {field_name}: '{value}' | Error in get_or_create for {table}: {str(e)}")
        raise

def log_row_summary(index, row, status, message=""):
    """Log a summary of the current row being processed."""
    title = clean_value(row.get('TITLE', ''), PLACEHOLDER_TITLE)
    authors = clean_value(row.get('AUTHOR', ''), '')
    isbn = clean_value(row.get('ISBN', ''), 'No ISBN')
    log_msg = f"Row {index+1} | Title: '{title}' | Author(s): '{authors}' | ISBN: '{isbn}' | Status: {status}"
    if message:
        log_msg += f" | {message}"
    logging.info(log_msg)

try:
    total_rows = len(df)
    success_count = 0
    error_count = 0
    
    logging.info(f"Starting import of {total_rows} records")
    
    for index, row in df.iterrows():
        # Initialize book state for this row
        book = None
        book_id = None
        
        try:
            # Log start of row processing
            log_row_summary(index, row, "Processing")
            
            # 1. Process Publisher
            publisher_name = clean_value(row.get('PUBLISHER', ''), PLACEHOLDER_PUBLISHER, 'PUBLISHER', index)
            if publisher_name != PLACEHOLDER_PUBLISHER:  # Only normalize non-placeholder publishers
                # Normalize the publisher name for consistent comparison
                normalized_publisher = normalize_publisher(publisher_name, index)
                publisher_name = normalized_publisher
                
                if not publisher_name:  # If publisher becomes empty after normalization
                    publisher_name = PLACEHOLDER_PUBLISHER
                    logging.warning(f"Row {index+1} | Publisher became empty after normalization, using placeholder")
            try:
                publisher_id = get_or_create('TBL_PUBLISHERS', 'name', publisher_name, 'name', index, 'Publisher')
                if not publisher_id:
                    raise ValueError("Failed to get or create publisher")
            except Exception as e:
                logging.warning(f"Row {index+1} | Publisher: '{publisher_name}' | Using placeholder publisher due to error: {str(e)}")
                publisher_id = get_or_create('TBL_PUBLISHERS', 'name', PLACEHOLDER_PUBLISHER, 'name', index, 'Publisher')
            
            # 2. Process Author(s)
            author_ids = []
            author_field = clean_value(row.get('AUTHOR', ''), '', 'AUTHOR', index)
            
            try:
                # Parse all authors from the author field
                authors = parse_authors(author_field, index)
                
                for first_name, middle_name, last_name in authors:
                    try:
                        # Get or create author and add to our list
                        author_id = get_or_create_author(first_name, middle_name, last_name, index)
                        if author_id and author_id not in author_ids:
                            author_ids.append(author_id)
                        elif not author_id:
                            logging.warning(f"Row {index+1} | Author: '{first_name} {middle_name} {last_name}' | Failed to get/create author, skipping")
                    except Exception as e:
                        logging.error(f"Row {index+1} | Author: '{first_name} {middle_name} {last_name}' | Error processing author: {str(e)}")
                        # Add a placeholder author if there's an error
                        try:
                            placeholder_author_id = get_or_create_author(
                                f"{PLACEHOLDER_FIRST_NAME}_{index}",
                                PLACEHOLDER_MIDDLE_NAME,
                                f"{PLACEHOLDER_LAST_NAME}_{index}",
                                index
                            )
                            if placeholder_author_id not in author_ids:
                                author_ids.append(placeholder_author_id)
                        except Exception as e2:
                            logging.error(f"Row {index+1} | Failed to create placeholder author: {str(e2)}")
                        continue
                
                # If no valid authors were found, use a placeholder
                if not author_ids:
                    logging.warning(f"Row {index+1} | No valid authors found, using placeholder author")
                    try:
                        placeholder_id = get_or_create_author(
                            PLACEHOLDER_FIRST_NAME, 
                            PLACEHOLDER_MIDDLE_NAME, 
                            PLACEHOLDER_LAST_NAME,
                            index
                        )
                        author_ids.append(placeholder_id)
                    except Exception as e:
                        logging.error(f"Row {index+1} | Failed to create placeholder author: {str(e)}")
                        raise
                    
            except Exception as e:
                logging.error(f"Row {index+1} | Error parsing authors: {str(e)}")
                try:
                    author_ids = [get_or_create_author(
                        PLACEHOLDER_FIRST_NAME, 
                        PLACEHOLDER_MIDDLE_NAME, 
                        PLACEHOLDER_LAST_NAME,
                        index
                    )]
                    logging.warning(f"Row {index+1} | Using placeholder author due to error")
                except Exception as e2:
                    logging.critical(f"Row {index+1} | CRITICAL: Failed to create placeholder author: {str(e2)}")
                    raise ValueError(f"Row {index+1} | CRITICAL: Failed to create placeholder author: {str(e2)}")

            # 3. Process Book - Clean and normalize fields
            title = clean_value(row.get('TITLE', ''), PLACEHOLDER_TITLE, 'TITLE', index)
            if title != PLACEHOLDER_TITLE:  # Only normalize non-placeholder titles
                title = ' '.join(title.split())  # Normalize whitespace
                if not title:  # If title becomes empty after normalization
                    title = PLACEHOLDER_TITLE
                    logging.warning(f"Row {index+1} | Title became empty after normalization, using placeholder")
            # Process book details
            call_no = clean_value(row.get('CALL NO.', ''), PLACEHOLDER_CALL_NO, 'CALL NO.', index)
            # Get accession number
            accession = clean_value(
                row.get('ACCESSION', ''), 
                f"{PLACEHOLDER_ACCESSION}_{index}",  # Make placeholder unique per row
                'ACCESSION', 
                index
            )
            # Get edition/volume - using clean_edition through clean_value
            edition = clean_value(row.get('ED./VOL.', None), field_name='ED./VOL.', row_index=index)
            # Get pages
            raw_pages = row.get('PAGES', 0)
            pages = clean_int(raw_pages, default=0, row_index=index, field_name='PAGES')
            
            # Process ISBN and cover image
            raw_isbn = row.get('ISBN', '')
            isbn = normalize_isbn(raw_isbn)
            
            # Log if ISBN was modified during normalization
            if raw_isbn and str(raw_isbn).strip().lower() not in ['not found', 'none', 'nan', 'n/a', ''] and isbn is None:
                logging.warning(f"Row {index+1} | ISBN: Invalid format for '{raw_isbn}', setting to NULL")
            elif raw_isbn and str(raw_isbn).strip() != str(isbn or ''):
                logging.info(f"Row {index+1} | ISBN normalized: '{raw_isbn}' -> '{isbn}'")
            
            # Handle cover image only if we have a valid ISBN
            cover_image = None
            if isbn and isbn in available_covers:
                cover_image = f"{isbn}.webp"
                logging.info(f"Row {index+1} | Found cover image for ISBN: {isbn}")
            
            # Book creation: checks for title and publisher, adds edition
            try:
                # Get pages if available, extract integer from string if needed
                raw_pages = row.get('PAGES', 0)
                pages = clean_int(raw_pages, default=0, row_index=index, field_name='PAGES')
                
                # Log the book lookup details with normalized values
                logging.info(f"Row {index+1}: Lookup - Title='{title}' (placeholder={title==PLACEHOLDER_TITLE}), PublisherID={publisher_id} (placeholder={publisher_name==PLACEHOLDER_PUBLISHER}), Edition='{edition}', ISBN='{isbn}'")
                
                # Log the raw values for comparison
                logging.debug(f"Row {index+1}: Raw values - Title='{row.get('TITLE', '')}', Publisher='{row.get('PUBLISHER', '')}', Edition='{row.get('ED./VOL.', '')}', ISBN='{row.get('ISBN', '')}'")
                
                # Only try to find existing book if we have valid title and publisher (not placeholders)
                if title != PLACEHOLDER_TITLE and publisher_name != PLACEHOLDER_PUBLISHER:
                    # First try to find by ISBN if available
                    if isbn:
                        logging.info(f"Row {index+1}: Searching by ISBN='{isbn}'")
                        try:
                            cursor.execute(
                                "SELECT book_id, title, publisher_id, edition, isbn FROM TBL_BOOKS WHERE isbn = %s",
                                (isbn,)
                            )
                            book = cursor.fetchone()
                            if book:
                                book_id, book_title, book_pub_id, book_edition, book_isbn = book
                                logging.info(f"Row {index+1}: FOUND by ISBN='{isbn}' -> ID: {book_id} (Title: '{book_title}', PublisherID: {book_pub_id}, Edition: '{book_edition}')")
                        except Exception as e:
                            logging.error(f"Row {index+1}: Error searching by ISBN '{isbn}': {str(e)}")
                            book = None
                    
                    # If not found by ISBN or no ISBN, search by title, publisher, and edition
                    if not book and title and publisher_id:
                        logging.info(f"Row {index+1}: Searching by Title='{title}', PublisherID={publisher_id}, Edition='{edition}'")
                        try:
                            cursor.execute(
                                """
                                SELECT book_id, title, publisher_id, edition, isbn 
                                FROM TBL_BOOKS 
                                WHERE title = %s 
                                AND publisher_id = %s 
                                AND COALESCE(edition, '') = COALESCE(%s, '')
                                """,
                                (title, publisher_id, edition)
                            )
                            book = cursor.fetchone()
                            if book:
                                book_id, book_title, book_pub_id, book_edition, book_isbn = book
                                logging.info(f"Row {index+1}: FOUND by Title+Publisher+Edition -> ID: {book_id} (Title: '{book_title}', PublisherID: {book_pub_id}, Edition: '{book_edition}', ISBN: '{book_isbn}')")
                        except Exception as e:
                            logging.error(f"Row {index+1}: Error searching by Title+Publisher+Edition: {str(e)}")
                            book = None
                
                # If we found a book, use it; otherwise, create a new one
                if book:
                    book_id = book[0]
                    logging.info(f"Row {index+1} | Using existing book ID: {book_id}")
                else:
                    # Log the book creation
                    logging.info(f"Row {index+1}: Creating NEW book - Title='{title}', PublisherID={publisher_id}, Edition='{edition}', ISBN='{isbn}'")
                    
                    # Prepare the insert with optional edition field
                    insert_sql = """
                        INSERT INTO TBL_BOOKS (
                            title, 
                            publisher_id, 
                            pages, 
                            isbn, 
                            cover_image
                            {edition_field}
                        ) VALUES (%s, %s, %s, %s, %s{edition_value})
                    """
                    
                    # Always include edition in the insert, using NULL for None
                    insert_sql = insert_sql.format(
                        edition_field=",\n                            edition" if edition is not None else "",
                        edition_value=", %s" if edition is not None else ""
                    )
                    
                    # Build parameters list
                    params = [title, publisher_id, pages, isbn, cover_image]
                    if edition is not None:
                        params.append(edition)
                    
                    cursor.execute(insert_sql, params)
                    db.commit()
                    book_id = cursor.lastrowid
                    logging.info(f"Row {index+1} | Created new book ID: {book_id}")
                    
                    # Update book to ensure we have the latest data
                    cursor.execute("SELECT * FROM TBL_BOOKS WHERE book_id = %s", (book_id,))
                    book = cursor.fetchone()
                    logging.debug(f"Row {index+1} | Book created with data: {book}")

                # Add book copy
                cursor.execute(
                    """
                    INSERT INTO TBL_BOOK_COPIES (book_id, accession_no, call_no, edition, status)
                    VALUES (%s, %s, %s, %s, 'Available')
                    """,
                    (book_id, accession, call_no, edition)
                )
                copy_id = cursor.lastrowid
                logging.info(f"Row {index+1} | Created book copy ID: {copy_id}")

                # Link each author to the book with order
                for author_index, author_id in enumerate(author_ids):
                    try:
                        cursor.execute(
                            """
                            INSERT IGNORE INTO TBL_BOOK_AUTHORS (book_id, author_id, author_order)
                            VALUES (%s, %s, %s)
                            """,
                            (book_id, author_id, author_index + 1)
                        )
                        logging.debug(f"Row {index+1} | Linked author {author_id} to book {book_id} (order: {author_index + 1})")
                    except Exception as e:
                        logging.error(f"Row {index+1} | Failed to link author {author_id} to book {book_id}: {str(e)}")
                        continue  # Continue with other authors even if one fails
                
                db.commit()
                success_count += 1
                log_row_summary(index, row, "Success", f"Book ID: {book_id}, Copy ID: {copy_id}")
                logging.info(f"Row {index+1} | Successfully processed book '{title}'")
                
            except Exception as e:
                db.rollback()
                error_count += 1
                logging.error(f"Row {index+1} | Error processing book: {str(e)}")
                log_row_summary(index, row, "Failed", str(e))
                continue
                
        except Exception as e:
            error_count += 1
            logging.error(f"Row {index+1} | Critical error processing row: {str(e)}", exc_info=True)
            log_row_summary(index, row, "Failed", f"Critical error: {str(e)}")
            continue

    # Log final summary
    logging.info("=" * 80)
    logging.info(f"IMPORT SUMMARY - Total: {total_rows}, Success: {success_count}, Errors: {error_count}")
    logging.info("=" * 80)
    
    if error_count > 0:
        logging.warning(f"Completed with {error_count} errors. Please check the log for details.")
    else:
        logging.info("Import completed successfully!")
    
except Exception as e:
    error_msg = f"FATAL ERROR: {str(e)}"
    logging.critical(error_msg, exc_info=True)
    print(error_msg)
    if 'db' in locals():
        db.rollback()
    
finally:
    if 'cursor' in locals():
        cursor.close()
    if 'db' in locals():
        db.close()
    logging.info("Database connections closed")
