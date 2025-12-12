# 1. Define the directories
dirs="app bootstrap/app.php config database/factories database/migrations database/seeders routes Modules/EmailMigration Modules/Crm"

# 2. Loop through them
for path in $dirs; do
    echo "==================================================="
    echo "🧹  Cleaning Cache & Resting for 5 seconds..."
    php vendor/bin/phpstan clear-result-cache --quiet
    sleep 5
    
    echo "🔎  ANALYZING: $path (Single Threaded)"
    outfile="phpstan_$(echo $path | tr '/' '_').txt"
    
    # 3. Run with --debug (Forces Single Process)
    # Removed: --procs 1 (Invalid)
    # Added: --debug (Disables parallelism)
    php -d memory_limit=4G -d opcache.enable_cli=0 -d opcache.jit=disable \
    vendor/bin/phpstan analyse "$path" \
    --configuration=phpstan.neon \
    --level=9 \
    --debug \
    --no-progress \
    --error-format=table > "$outfile" 2>&1
    
    # 4. Check if the file has content (Analysis finished)
    if [ -s "$outfile" ]; then
        echo "✅  SUCCESS: $path"
    else
        echo "❌  CRASHED: $path (File is empty)"
    fi
done

echo "==================================================="
echo "🏁  Batch Run Complete."