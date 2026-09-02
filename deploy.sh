#!/bin/bash
set -e

COMPOSE="docker compose -f docker-compose.prod.yml --env-file .env.production"

echo "==> Paso 1: preparando .env.production"
if [ ! -f .env.production ]; then
    cp .env.production.example .env.production
    sed -i "s|^APP_KEY=.*|APP_KEY=base64:HTEC1ACnPqab0i0CfznQLRpiZYdAARxLPloy0GL64bw=|" .env.production
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=2f6d1a28a3403f49084402c1|" .env.production
    echo "    .env.production creado."
else
    echo "    .env.production ya existe, no se toca."
fi

echo "==> Paso 2: apagando y limpiando contenedores/volúmenes anteriores"
$COMPOSE down -v || true

echo "==> Paso 3: construyendo la imagen (puede tardar unos minutos)"
$COMPOSE build

echo "==> Paso 4: levantando los contenedores"
$COMPOSE up -d

echo "==> Paso 5: esperando a que la app responda (hasta 60s)"
ok=0
for i in $(seq 1 20); do
    if $COMPOSE exec -T app php artisan about > /dev/null 2>&1; then
        ok=1
        break
    fi
    sleep 3
done

if [ "$ok" -eq 0 ]; then
    echo "==> ERROR: la app no arrancó. Estos son los últimos logs:"
    $COMPOSE logs --tail=60 app
    exit 1
fi

echo "==> Paso 6: creando roles"
$COMPOSE exec -T app php artisan db:seed --class=RoleSeeder --force

echo "==> Paso 7: creando usuario administrador"
$COMPOSE exec -T app php artisan tinker --execute="
if (! App\Models\User::where('email', 'admin@tuempresa.com')->exists()) {
    \$admin = App\Models\User::create(['name' => 'Administrador', 'email' => 'admin@tuempresa.com', 'password' => Hash::make('CambiaEsta123')]);
    \$admin->assignRole('admin');
    echo 'Admin creado: '.\$admin->email;
} else {
    echo 'El admin ya existía, no se creó de nuevo.';
}
"

echo ""
echo "==> LISTO. Entra en http://TU_IP_O_DOMINIO:8090"
echo "    correo:      admin@tuempresa.com"
echo "    contraseña:  CambiaEsta123"
