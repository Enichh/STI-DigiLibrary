from PIL import Image
import os

def convertImageToWebp(sourcePath: str, destPath: str, quality: int = 85) -> bool:
    """Converts an image at sourcePath to WebP format and saves at destPath."""
    try:
        with Image.open(sourcePath) as img:
            img.save(destPath, 'WEBP', quality=quality)
        return True
    except Exception:
        return False

# Example usage for all jpg/png images in /covers
def batchConvertToWebp(imageDir: str, removeOriginal: bool = False):
    """
    Converts all JPG/JPEG/PNG images in the directory to WebP format.
    
    Args:
        imageDir (str): Directory containing images to convert
        removeOriginal (bool): If True, deletes original files after successful conversion
    """
    for fileName in os.listdir(imageDir):
        if fileName.lower().endswith(('.jpg', '.jpeg', '.png')):
            sourcePath = os.path.join(imageDir, fileName)
            destName = os.path.splitext(fileName)[0] + '.webp'
            destPath = os.path.join(imageDir, destName)
            success = convertImageToWebp(sourcePath, destPath)
            status = "Converted" if success else "Failed"
            print(f"{status}: {fileName} → {destName}")
            if success and removeOriginal:
                try:
                    os.remove(sourcePath)
                    print(f"Removed original: {fileName}")
                except OSError as e:
                    print(f"Warning: Could not remove {fileName}: {e}")

# To convert all covers once done downloading:
if __name__ == "__main__":
    coversDir = "C:/xampp/htdocs/STI-DigiLibrary/covers"
    # Convert all images to WebP and remove originals after successful conversion
    batchConvertToWebp(coversDir, removeOriginal=True)
