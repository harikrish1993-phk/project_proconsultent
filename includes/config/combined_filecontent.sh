#!/bin/bash

# Output file
output_file="test.txt"

# Directories to include (relative paths)
include_dirs=(

)

# Specific files in the root directory
include_files=(

)

# Clear previous output
: > "$output_file"

separator="\n-----------------------------\n"

# --- Include specific root files ---
echo "📂 Including root files..."
for file in "${include_files[@]}"; do
  if [ -f "$file" ]; then
    {
      echo "File: ./$file"
      echo "Content:"
      cat "$file"
      echo -e "$separator"
    } >> "$output_file"
  else
    echo "⚠️ File not found: $file"
  fi
done

# --- Include all files from specified directories ---
for dir in "${include_dirs[@]}"; do
  if [ -d "$dir" ]; then
    echo "📂 Processing directory: $dir"
    while IFS= read -r file; do
      {
        echo "File: $file"
        echo "Content:"
        cat "$file"
        echo -e "$separator"
      } >> "$output_file"
    done < <(find "$dir" -type f)
  else
    echo "⚠️ Directory not found: $dir"
  fi
done

echo "✅ Combined file created: $output_file"
