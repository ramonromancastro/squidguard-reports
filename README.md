![Logo](images/complete.logo.png)

# squidGuard Reports

squidGuard Reports es una aplicación que se encarga de parsear los archivos de registro generados por squidGuard, para ser procesados y presentados mediante el uso de una interfaz Web.

## Características

- Interfaz Web facil e intuitiva.
- Informes diferenciados por fecha, usuario/dirección ip, sitios y clasificaciones.
- Informes detallados por horas para los accesos de los usuarios/direcciones ip.

## Instalación

Clonar el repositorio en una ruta accesible desde el servidor Web.
```
cd /var/www/html
git clone https://github.com/ramonromancastro/squidguardreports.git
```
Crear los archivos de configuración y establecer las opciones de configuración.
```
cp squidguardparser.template.cfg squidguardparser.cfg
cp config.template.php config.php
```
Crear las tareas programadas que se encargan de la recolección de los archivos de registro.
```
echo "#!/bin/bash" > /etc/cron.daily/squidguardreports.cron
echo "/var/www/html/squidguardreports/squidguardparser.pl yesterday" >> /etc/cron.daily/squidguardreports.cron
chmod +x /etc/cron.daily/squidguardreports.cron
echo "0 8-20 * * * root /var/www/html/squidguardreports/squidguardparser.pl today" > /etc/cron.d/squidguardreports.cron
```
Ejecutar squidguardparser.pl para la recolección inicial de los datos del registro.
```
/var/www/html/squidguardreports/squidguardparser.pl
```

## Recursos adicionales

* [Sitio Web oficial](http://www.rrc2software.com)
* [Repositorio GitHub](https://github.com/ramonromancastro/squidguardreports)
