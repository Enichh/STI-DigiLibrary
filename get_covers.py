import os
import pandas as pd
import requests

API_KEY = "AIzaSyCQSlOE89U7wltXwenQE6sqzPzP-Bjm44k"

def readExcelFile(filePath: str) -> pd.DataFrame:
    """Reads an Excel file and returns its DataFrame."""
    try:
        df = pd.read_excel(filePath)
        return df
    except Exception as e:
        raise Exception(f"Failed to read Excel file: {e}")

def extractIsbns(df: pd.DataFrame, isbnColumnName: str) -> list:
    """Extracts a list of ISBNs from the DataFrame."""
    if isbnColumnName not in df.columns:
        raise ValueError(f"Column '{isbnColumnName}' not found in Excel file.")
    return df[isbnColumnName].dropna().astype(str).tolist()

def getBookCoverAndTitle(isbn: str, apiKey: str) -> tuple:
    """Fetches the book title and cover URL from Google Books API for a given ISBN."""
    url = f"https://www.googleapis.com/books/v1/volumes?q=isbn:{isbn}&key={apiKey}"
    try:
        response = requests.get(url)
        response.raise_for_status()
        data = response.json()
        items = data.get('items')
        if not items:
            return None, None
        volumeInfo = items[0]['volumeInfo']
        title = volumeInfo.get('title', 'Unknown Title')
        coverUrl = volumeInfo.get('imageLinks', {}).get('thumbnail')
        return title, coverUrl
    except Exception:
        return None, None

def downloadImage(url: str, savePath: str) -> bool:
    """Downloads an image from a URL to the specified path."""
    if url is None:
        return False
    try:
        response = requests.get(url, timeout=10)
        response.raise_for_status()
        with open(savePath, 'wb') as file:
            file.write(response.content)
        return True
    except Exception:
        return False

def saveBookCovers(isbns: list, apiKey: str, saveDir: str):
    """Downloads book cover images and logs the actual book titles for a list of ISBNs."""
    if not os.path.exists(saveDir):
        os.makedirs(saveDir)
    for isbn in isbns:
        title, coverUrl = getBookCoverAndTitle(isbn, apiKey)
        fileName = f"{isbn}.jpg"
        filePath = os.path.join(saveDir, fileName)
        success = downloadImage(coverUrl, filePath)
        bookTitle = title if title else 'Title not found'
        if success:
            print(f"Saved cover for '{bookTitle}'")
        else:
            print(f"Failed to save cover for '{bookTitle}'")

def main():
    filePath = r"C:\xampp\htdocs\STI-DigiLibrary\LIBRARY-HOLDINGS-IT-WITH-ISBN.xlsx"
    isbnColumnName = "ISBN"  # Change if needed to match your Excel column
    coversDir = os.path.join(os.path.dirname(filePath), "covers")
    
    # Read and process ISBNs
    df = readExcelFile(filePath)
    isbns = extractIsbns(df, isbnColumnName)
    
    # Log and handle duplicates
    duplicateIsbns = {isbn for isbn in isbns if isbns.count(isbn) > 1}
    if duplicateIsbns:
        print(f"Note: Found {len(duplicateIsbns)} duplicate ISBNs. Processing each unique ISBN only once.")
        if len(duplicateIsbns) <= 10:  # Only show duplicates if not too many
            print(f"Duplicate ISBNs: {', '.join(sorted(duplicateIsbns))}")
    
    # Process only unique ISBNs while maintaining order
    uniqueIsbns = list(dict.fromkeys(isbns))
    print(f"Processing {len(uniqueIsbns)} unique ISBNs...")
    
    saveBookCovers(uniqueIsbns, API_KEY, coversDir)

if __name__ == "__main__":
    main()
