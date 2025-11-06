# Testing Guide for Resolate

Esta guía explica cómo configurar y ejecutar los tests de PHPUnit para el plugin Resolate.

## Tabla de Contenidos

- [Opción 1: Ejecutar tests con Docker (Recomendado)](#opción-1-ejecutar-tests-con-docker-recomendado)
- [Opción 2: Ejecutar tests nativamente (Sin Docker)](#opción-2-ejecutar-tests-nativamente-sin-docker)
- [Ejecutar tests específicos](#ejecutar-tests-específicos)
- [Estructura de tests](#estructura-de-tests)

---

## Opción 1: Ejecutar tests con Docker (Recomendado)

Esta es la opción más sencilla, ya que usa `wp-env` para crear un entorno de WordPress con Docker.

### Requisitos previos

- Docker instalado y ejecutándose
- Node.js y npm instalados

### Pasos

1. **Instalar @wordpress/env globalmente** (si aún no lo tienes):

```bash
npm install -g @wordpress/env
```

2. **Levantar el entorno de WordPress**:

```bash
make up
```

Este comando:
- Inicia contenedores Docker con WordPress
- Instala las dependencias necesarias
- Configura el plugin automáticamente

3. **Ejecutar todos los tests**:

```bash
make test
```

4. **Ejecutar tests específicos**:

```bash
# Ejecutar un archivo de test específico
make test FILE=tests/unit/includes/ResolateTest.php

# Ejecutar tests que coincidan con un patrón
make test FILTER=test_document_generation

# Combinar ambos
make test FILE=tests/unit/includes/ResolateTest.php FILTER=test_specific_method
```

5. **Ejecutar tests en modo verbose**:

```bash
make test-verbose
```

6. **Ver logs del entorno de tests**:

```bash
make logs-test
```

7. **Detener el entorno**:

```bash
make down
```

---

## Opción 2: Ejecutar tests nativamente (Sin Docker)

Si prefieres no usar Docker, puedes configurar el entorno de tests nativamente.

### Requisitos previos

- PHP 7.4 o superior
- MySQL o MariaDB
- Composer
- Subversion (svn)

### Pasos

1. **Instalar dependencias de Composer**:

```bash
composer install
```

2. **Configurar la base de datos de tests**:

Crea una base de datos MySQL para los tests:

```bash
mysql -u root -p -e "CREATE DATABASE wordpress_test;"
```

3. **Instalar WordPress Test Suite**:

Usa el script incluido para instalar el WordPress test suite:

```bash
bash bin/install-wp-tests.sh wordpress_test root 'tu_password' localhost latest
```

Parámetros:
- `wordpress_test`: Nombre de la base de datos
- `root`: Usuario de MySQL
- `tu_password`: Contraseña de MySQL
- `localhost`: Host de MySQL
- `latest`: Versión de WordPress (puede ser 'latest', 'nightly', '6.4', etc.)

4. **Configurar variables de entorno**:

Copia el archivo de ejemplo y ajusta los valores:

```bash
cp .env.testing.example .env.testing
```

Edita `.env.testing` con tus valores de configuración.

5. **Exportar variables de entorno**:

```bash
source .env.testing
```

O exporta manualmente:

```bash
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
export WP_DEVELOP_DIR=/tmp/wordpress
```

6. **Ejecutar los tests**:

Puedes usar el script auxiliar que configura automáticamente el entorno:

```bash
# Ejecutar todos los tests
./bin/run-tests.sh

# Ejecutar tests específicos
./bin/run-tests.sh tests/unit/includes/ResolateTest.php

# Ejecutar con filtro
./bin/run-tests.sh --filter=test_document_generation

# Modo verbose
./bin/run-tests.sh --debug --verbose
```

O ejecutar directamente con Composer/PHPUnit:

```bash
# Ejecutar todos los tests
composer test
# O directamente con PHPUnit
./vendor/bin/phpunit

# Ejecutar tests específicos
./vendor/bin/phpunit tests/unit/includes/ResolateTest.php

# Ejecutar con filtro
./vendor/bin/phpunit --filter=test_document_generation

# Modo verbose
./vendor/bin/phpunit --debug --verbose
```

---

## Ejecutar tests específicos

### Por archivo

```bash
# Con make (Docker)
make test FILE=tests/unit/includes/ResolateTest.php

# Con PHPUnit directo
./vendor/bin/phpunit tests/unit/includes/ResolateTest.php
```

### Por método o patrón

```bash
# Con make (Docker)
make test FILTER=test_document_generation

# Con PHPUnit directo
./vendor/bin/phpunit --filter=test_document_generation
```

### Por grupo (si están definidos)

```bash
./vendor/bin/phpunit --group=admin
./vendor/bin/phpunit --exclude-group=slow
```

---

## Estructura de tests

```
tests/
├── bootstrap.php                           # Bootstrap de PHPUnit
├── fixtures/                               # Archivos de prueba (PDFs, imágenes, etc.)
│   ├── sample-1.pdf
│   └── sample-2.jpg
├── includes/                               # Clases auxiliares para tests
│   ├── class-wp-unittest-resolate-test-base.php
│   ├── class-wp-unittest-factory-for-resolate-doc-type.php
│   └── class-wp-unittest-factory-for-resolate-document.php
└── unit/                                   # Tests unitarios
    ├── admin/                              # Tests de la parte admin
    ├── doc-type/                           # Tests de tipos de documento
    └── includes/                           # Tests de funcionalidad principal
        ├── custom-post-types/              # Tests de CPTs
        └── document/                       # Tests de documentos
```

---

## Solución de problemas

### Error: "WordPress native unit test bootstrap file could not be found"

Asegúrate de que las variables de entorno `WP_TESTS_DIR` o `WP_DEVELOP_DIR` estén configuradas:

```bash
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
export WP_DEVELOP_DIR=/tmp/wordpress
```

### Error: "ERROR 1045 (28000): Access denied for user"

Verifica las credenciales de MySQL al ejecutar el script `install-wp-tests.sh`.

### Tests fallan al conectar a la base de datos

Verifica que:
1. MySQL esté ejecutándose
2. La base de datos de tests exista
3. Las credenciales en `.env.testing` sean correctas

### Docker: "wp-env is NOT running"

Asegúrate de que Docker esté ejecutándose:

```bash
docker version
```

---

## Comandos útiles del Makefile

```bash
make help              # Ver todos los comandos disponibles
make test              # Ejecutar tests
make test-verbose      # Tests en modo verbose
make check             # Ejecutar linting, tests y otras validaciones
make lint              # Verificar estilo de código
make fix               # Corregir automáticamente estilo de código
```

---

## Recursos adicionales

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Plugin Handbook: Automated Testing](https://developer.wordpress.org/plugins/testing/automated-testing/)
- [Yoast WP Test Utils](https://github.com/Yoast/wp-test-utils)
- [@wordpress/env Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)
