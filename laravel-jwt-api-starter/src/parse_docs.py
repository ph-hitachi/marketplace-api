import re
from bs4 import BeautifulSoup

file_path = r"C:\Users\JessD\.gemini\antigravity-ide\brain\ab24b74e-2bde-480d-b9e4-907f86ae7c60\.system_generated\steps\14590\content.md"

with open(file_path, "r", encoding="utf-8") as f:
    html = f.read()

soup = BeautifulSoup(html, "html.parser")

# Print all headings
print("--- HEADINGS ---")
for h in soup.find_all(["h1", "h2", "h3", "h4"]):
    print(h.name, h.get_text(strip=True))

# Search for sorting or weight in text
print("\n--- SEARCH RESULTS ---")
text = soup.get_text()
for line in text.splitlines():
    if any(w in line.lower() for w in ["sort", "weight", "group"]):
        print(line.strip())
