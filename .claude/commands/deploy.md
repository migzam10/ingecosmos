# Comando: /deploy
# Uso: /deploy
# Prepara el proyecto para subir al hosting latinoamericahosting.com.co

Lee CLAUDE.md para recordar la configuración del hosting.

Hosting: latinoamericahosting.com.co
Panel: cPanel
PHP: 8.x activo
MySQL: activo
Laravel: 11.53.1

Pasos de deploy:
1. Verificar que no hay errores en el código local
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   php artisan optimize
   ```

2. Crear el archivo .env de producción (NO commitear, copiar manualmente al hosting)
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://tudominio.com
   DB_HOST=localhost
   DB_DATABASE=[nombre_db_cpanel]
   DB_USERNAME=[usuario_cpanel]
   DB_PASSWORD=[password_cpanel]
   ```

3. Subir archivos via FTP o Git al hosting
   - Si el hosting soporta Git: conectar repositorio GitHub
   - Si es FTP: subir todos los archivos EXCEPTO: node_modules/, .env, storage/logs/

4. En el hosting via SSH o File Manager:
   ```bash
   composer install --optimize-autoloader --no-dev
   php artisan migrate --force
   php artisan db:seed --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

5. Verificar que public/ es la carpeta raíz del dominio en cPanel

Mostrar checklist completo antes de ejecutar cualquier paso.
