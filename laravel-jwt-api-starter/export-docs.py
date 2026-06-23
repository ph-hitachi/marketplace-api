import os
import json
import subprocess

def main():
    print("Exporting raw OpenAPI JSON via Scramble...")
    
    # Run the export inside docker, saving it inside the container to a temporary file
    result = subprocess.run([
        "docker", "compose", "exec", "app", 
        "php", "artisan", "scramble:export", "--path=openapi.json"
    ], capture_output=True, text=True)
    
    if result.returncode != 0:
        print("Failed to export OpenAPI JSON.")
        print(result.stderr)
        return

    # The file was created at src/openapi.json on the host
    source_path = os.path.join("src", "openapi.json")
    target_path = os.path.join("docs", "api", "openapi.json")
    
    if not os.path.exists(source_path):
        print(f"File not found at {source_path}")
        return

    print("Grouping schemas by type...")
    
    with open(source_path, "r", encoding="utf-8") as f:
        data = json.load(f)
        
    schemas = data.get('components', {}).get('schemas', {})
    if not schemas:
        print("No schemas found to group.")
        return
        
    new_schemas = {}
    replacements = {}
    
    for key, schema in schemas.items():
        if key.endswith("Request"):
            new_key = "Requests." + key
        elif key.endswith("Resource") or key.endswith("Collection"):
            new_key = "Resources." + key
        else:
            new_key = "Models." + key
            
        # Update the title so Stoplight Elements displays it correctly
        if 'title' in schema:
            schema['title'] = new_key
            
        new_schemas[new_key] = schema
        replacements[f'"#/components/schemas/{key}"'] = f'"#/components/schemas/{new_key}"'
        
    # Sort alphabetically
    sorted_schemas = dict(sorted(new_schemas.items()))
    data['components']['schemas'] = sorted_schemas
    
    # Serialize back to string
    json_str = json.dumps(data, indent=4, ensure_ascii=False)
    
    # Replace all references
    for old_ref, new_ref in replacements.items():
        json_str = json_str.replace(old_ref, new_ref)
        
    # Write to the final target path
    with open(target_path, "w", encoding="utf-8") as f:
        f.write(json_str)
        
    # Clean up the temporary file
    os.remove(source_path)
    
    print(f"Successfully grouped schemas and saved to {target_path}")

if __name__ == "__main__":
    main()
