# MCP Server - Finanzas

Servidor MCP (Model Context Protocol) para gestión de finanzas personales.

## Tecnologías

- **PHP** 8.1+
- **Base de datos**: MySQL o SQLite
- **Protocolo**: MCP (Model Context Protocol) sobre HTTP

## Librerías

| Paquete | Versión | Descripción |
|---------|---------|-------------|
| `mcp/sdk` | ^0.4.0 | SDK oficial de MCP para PHP |
| `illuminate/database` | ^11.0 | Eloquent ORM (Laravel) |
| `nyholm/psr7` | ^1.8 | Implementación de PSR-7 |
| `nyholm/psr7-server` | ^1.1 | Adaptador de servidor PSR-7 |

## Requisitos

- PHP 8.1 o superior
- Composer
- MySQL 5.7+ o SQLite 3
- Extensiones PHP: `pdo`, `json`, `mbstring`

## Instalación

```bash
# Instalar dependencias
composer install
```

## Configuración

Crear archivo `.env` con las credenciales de la base de datos:

```env
DB_HOST=localhost
DB_NAME=finanzas
DB_USER=root
DB_PASSWORD=tu_password
DB_PORT=3306
DB_DRIVER=mysql
DB_CHARSET=utf8
```

Para SQLite, usar:

```env
DB_DRIVER=sqlite
DB_DATABASE=finanzas.db
```

## Ejecución

### Servidor de desarrollo (PHP built-in)

```bash
php -S 127.0.0.1:8080 -t public
```

### Usando Composer scripts

```bash
composer run server
```

### Apache

1. Crear un virtual host:

```apache
<VirtualHost *:80>
    ServerName finanzas-mcp.local
    DocumentRoot /path/to/finanzas-mcp/public
    
    <Directory /path/to/finanzas-mcp/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
        
        # Rewrites para eliminar index.php
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>

    # Configuración de logs
    ErrorLog ${APACHE_LOG_DIR}/finanzas-mcp-error.log
    CustomLog ${APACHE_LOG_DIR}/finanzas-mcp-access.log combined
</VirtualHost>
```

2. Habilitar el sitio y reiniciar Apache:

```bash
# En Debian/Ubuntu
sudo a2ensite finanzas-mcp.conf
sudo a2enmod rewrite
sudo systemctl restart apache2

# En Windows (XAMPP)
# Copiar el archivo de configuración a C:\xampp\apache\conf\extra\
# Editar C:\xampp\apache\conf\httpd.conf para incluir el archivo
```

3. Agregar al archivo hosts:

```hosts
# Windows
127.0.0.1 finanzas-mcp.local

# Linux/Mac
sudo bash -c 'echo "127.0.0.1 finanzas-mcp.local" >> /etc/hosts'
```

## Herramientas MCP Disponibles

### get_outflow_types
Obtiene los tipos de egreso activos.

### get_categories
Obtiene las categorías filtradas por tipo de egreso.

### get_available_by_deposits
Obtiene todos los depósitos con su balance disponible (ingresos - egresos).

### outflow_money
Crea un nuevo registro de egreso.

Parámetros:
- `idOutflowType` (requerido): ID del tipo de egreso
- `idCategory` (requerido): ID de la categoría
- `idPorcent` (requerido): ID del depósito
- `amount` (requerido): Monto a retirar (> 0)
- `setDate` (opcional): Fecha del egreso
- `isInBudget` (opcional): Si está en presupuesto (default: true)
- `description` (opcional): Descripción adicional
- `idUser` (opcional): ID del usuario (default: 1)
- `dryRun` (opcional): Validar sin persistir (default: false)

## Comandos útiles

```bash
# Instalar dependencias
composer install

# Actualizar dependencias
composer update

# Ver dependencias instaladas
composer show

# Ver autoload
composer dump-autoload

# Ver configuración
php -i | grep -i pdo
```

## Estructura del proyecto

```
finanzas-mcp/
├── public/
│   └── index.php          # Punto de entrada
├── src/
│   ├── Database/
│   │   └── Connection.php # Conexión a BD
│   └── MCP/
│       ├── Server.php     # Servidor MCP
│       └── Tools/
│           ├── BaseTool.php
│           └── EgressMoney/
│               ├── GetOutflowTypesTool.php
│               ├── GetCategoriesTool.php
│               ├── GetAvailableByDepositsTool.php
│               └── OutflowMoneyTool.php
├── logs/                  # Archivos de log
├── sessions/             # Sesiones MCP
├── composer.json
├── .env
└── finanzas.db           # SQLite (si se usa)
```

## Troubleshooting

### Error de conexión a MySQL
Verificar que MySQL esté corriendo y las credenciales en `.env` sean correctas.

### Error de sesiones
Asegurarse de que el directorio `sessions/` tenga permisos de escritura.

### Error de logs
Asegurarse de que el directorio `logs/` tenga permisos de escritura.
