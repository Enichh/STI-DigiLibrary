import pandas as pd
import requests
import time
import logging
from urllib.parse import quote_plus
from fuzzywuzzy import fuzz

API_KEY = "AIzaSyCQSlOE89U7wltXwenQE6sqzPzP-Bjm44k"
INPUT_XLSX = "C:/xampp/htdocs/STI-DigiLibrary/LIBRARY HOLDINGS IT TRUE.xlsx"
OUTPUT_XLSX = "LIBRARY-HOLDINGS-IT-WITH-ISBN.xlsx"

logging.basicConfig(filename="isbn_lookup.log", level=logging.INFO)

df = pd.read_excel(INPUT_XLSX)
df['ISBN'] = ""

def best_google_books_match(title, author, data):
    if 'items' not in data or not data['items']:
        return None
    best_score = 0
    best_item = None
    search_title = title.lower()
    search_author = author.lower()
    for item in data['items']:
        info = item.get('volumeInfo', {})
        candidate_title = info.get('title', '').lower()
        candidate_authors = ' '.join(info.get('authors', [])).lower()
        score = fuzz.partial_ratio(search_title, candidate_title) + fuzz.partial_ratio(search_author, candidate_authors)
        if score > best_score:
            best_score = score
            best_item = item
    if best_score < 120:  # Threshold for match confidence
        return None
    return best_item

def fetch_isbn_from_google_books(title, author, retries=3):
    query = f"https://www.googleapis.com/books/v1/volumes?q=intitle:{quote_plus(title)}+inauthor:{quote_plus(author)}&key={API_KEY}"
    for attempt in range(retries):
        try:
            response = requests.get(query, timeout=10)
            if response.status_code == 200:
                data = response.json()
                match = best_google_books_match(title, author, data)
                if match:
                    info = match['volumeInfo']
                    identifiers = info.get('industryIdentifiers', [])
                    isbn_13 = next((id['identifier'] for id in identifiers if id['type'] == 'ISBN_13'), None)
                    isbn_10 = next((id['identifier'] for id in identifiers if id['type'] == 'ISBN_10'), None)
                    return isbn_13 or isbn_10
            else:
                logging.warning(f"Non-200 response for '{title}' by '{author}': {response.status_code}")
        except Exception as e:
            logging.error(f"Error on attempt {attempt+1} for '{title}' by '{author}': {e}")
            time.sleep(2 ** attempt)  # exponential backoff
    return None

for idx, row in df.iterrows():
    title = str(row.get('TITLE', '')).strip()
    author = str(row.get('AUTHOR', '')).strip()
    isbn = fetch_isbn_from_google_books(title, author)
    df.at[idx, 'ISBN'] = isbn if isbn else 'Not found'
    print(f"{idx+1}/{len(df)} | {title} / {author} -> ISBN: {isbn}")
    if not isbn:
        logging.info(f"{title},{author},Not found")
    time.sleep(1)

df.to_excel(OUTPUT_XLSX, index=False)
print(f"ISBN lookup completed. Results saved to {OUTPUT_XLSX}")
