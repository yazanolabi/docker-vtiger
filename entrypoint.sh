#!/bin/bash
set -e

cd /var/www/html

apache2-foreground &

# Espera a que Apache esté listo
echo "Esperando a que Apache se inicie..."
until curl -sSf http://crm.mabecenter.org/ > /dev/null; do
  echo "Apache aún no responde. Esperando 5 segundos..."
  sleep 5
done
echo "Apache ya está disponible. Ejecutando el instalador..."

# Ejecuta el instalador
composer require javanile/http-robot:0.0.2 --with-all-dependencies && php install.php

echo "Aplicando parches desde /patch..."

for patchfile in patch/*.patch; do
    if [ -f "$patchfile" ]; then
        echo "Ejecutando $patchfile..."
        case "$patchfile" in
            *add_related_field.patch*)
                patch --batch -p4 < patch/add_related_field.patch || true
                ;;
            *fix_error_id.patch*)
                patch --batch -p4 < patch/fix_error_id.patch || true
                ;;
            *)
                echo "No se reconoce la ubicación para $patchfile, omitiendo"
                ;;
        esac
    fi
done

sed -i 's|http://crm\.mabecenter\.org|https://crm.mabecenter.org|g' /var/www/html/config.inc.php

# php patch/ImportHelloWorld.php

echo "Parches aplicados. Iniciando el servicio..."
