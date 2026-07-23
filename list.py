import urllib.request
import json
import re

env = open(".env").read()
m = re.search(r"GEMINI_API_KEY=(.*)", env)
key = m.group(1).strip()

url = f"https://generativelanguage.googleapis.com/v1beta/models?key={key}"
try:
    req = urllib.request.urlopen(url)
    res = json.loads(req.read())
    for m in res['models']:
        if 'gemini' in m['name']:
            print(f"{m['name']} - {m.get('supportedGenerationMethods', [])}")
except urllib.error.HTTPError as e:
    print(f"Error {e.code}: {e.read().decode()}")
