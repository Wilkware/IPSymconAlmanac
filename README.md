# Almanac (Jahreskalender)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-5.2-blue.svg)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-3.2.20210126-orange.svg)](https://github.com/Wilkware/IPSymconAlmanac)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://github.com/Wilkware/IPSymconAlmanac/workflows/Check%20Style/badge.svg)](https://github.com/Wilkware/IPSymconAlmanac/actions)

Dieses Modul bietet Kalenderinformationen für Feiertage, Schulferien und andere Kalenderdaten.

## Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)
8. [Versionshistorie](#8-versionshistorie)

### 1. Funktionsumfang

Das Modul nutzt die von schulferien.org (www.schulferien.org) bereitgestellten ICS-Daten zur Anzeige der Feiertage und Schulferien
für Deutschland, Österreich und die Schweiz.  
Darüber hinaus werden mittels der PHP Funktion "date" verschiedene Informationen für das aktuelle Datum ermittelt.
In Kombination mit den ermittelten Feiertagen werden auch die Arbeitstage im aktuellen Monat bereitgestellt.

Folgende Informationen werden ermittelt:

* Sind Ferien und welche
* Feiertag oder nicht und wie heißt er
* Der Tag des Jahres
* Anzahl Tage im Monat
* Arbeitstage im Monat
* Schaltjahr oder nicht
* Sommerzeit oder nicht
* Wochenende oder nicht
* Nummer der Kalenderwoche

All diese Information können auch über die Methode [ALMANAC_DateInfo](#7-php-befehlsreferenz) als Array abgeholt werden.

Folgende Informationen stehen als key => value Paare zur Verfügung:

Index                 | Typ     | Beschreibung
--------------------- | ------- | ----------------
IsSummer              | bool    | TRUE, wenn Sommerzeit ist
IsLeapYear            | bool    | TRUE, wenn Schaltjahr ist
IsWeekend             | bool    | TRUE, wenn Wochenende ist (SA-SO)
WeekNumber            | int     | Kalenderwochennummer
DaysInMonth           | int     | Anzahl Tage im Monat
DayOfYear             | int     | Tag im Jahr (1-366)
WorkingDays           | int     | Arbeitstage im Monat
Holiday               | string  | Name des Feiertags, oder "Kein Feiertag"
IsHoliday             | bool    | TRUE, wenn Feiertag ist
SchoolHolidays        | string  | Name der Schulferien, oder "Keine Ferien"
IsSchoolHolidays      | bool    | TRUE, wenn Schulferienzeit ist

### 2. Voraussetzungen

* IP-Symcon ab Version 5.2

### 3. Installation

* Über den Modul Store das Modul Almanac installieren.
* Alternativ Über das Modul-Control folgende URL hinzufügen.  
`https://github.com/Wilkware/IPSymconAlmanac` oder `git://github.com/Wilkware/IPSymconAlmanac.git`

### 4. Einrichten der Instanzen in IP-Symcon

* Unter "Instanz hinzufügen" ist das 'Almanac'-Modul (Alias: Kalender, Schulferien, Feiertage) unter dem Hersteller '(Sonstige)' aufgeführt.

__Konfigurationsseite__:

Name               | Beschreibung
------------------ | ---------------------------------
Bundesland         | Auswahl des Bundesland für welchen man die Feiertage und Schulferien ermittelt haben möchte.
Basis URL          | Url zum Dienstanbieter für Feiertage und Schulferien, derzeit <https://www.schulferien.eu/downloads/ical4.php>
Feiertage          | Status, ob Ermittlung der Feiertage erwünscht ist.
Schulferien        | Status, ob Ermittlung der Schulferien erwünscht ist.
Datumsfunktion     | Status, ob Informationen zum aktuellen Datum erwünscht sind.

### 5. Statusvariablen und Profile

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
Feiertag             | String    | Name des Feriertages oder 'kein Feiertag'
Ist Feiertag         | Boolean   | Ist aktueller Tag ein Feiertag?
Ferien               | String    | Name der Schulferien oder 'keine Ferien'
Ist Ferienzeit       | Boolean   | Fällt aktueller Tag in die Ferien?
Ist Schaltjahr       | Boolean   | Ist aktueller Jahr ein Schaltjahr?
Ist Sommerzeit       | Boolean   | Ist aktuell Sommerzeit aktiv?
Ist Wochenende       | Boolean   | Ist gerade Wochenende?
Kalenderwoche        | Integer   | Nummer der aktuelle Kalenderwoche
Tag  im Jahr         | Integer   | Welcher Tag des Jahres?
Tage im Monat        | Integer   | Wieviel Tage hat der aktuelle Monat?
Arbeitstage im Monat | Integer   | Wieviel Arbeitstage hat der Monat des gewählten Bundeslandes?

Folgende Profile werden angelegt:

Name                 | Typ       | Beschreibung
-------------------- | --------- | ----------------
ALMANAC.Question     | Boolean   | FALSE = Nein / TRUE = Ja

### 6. WebFront

Man kann die Statusvariablen direkt im WF verlinken.

### 7. PHP-Befehlsreferenz

```php
void ALMANAC_Update(int $InstanzID):
```

Holt entsprechend der Konfiguration die gewählten Daten.  
Die Funktion liefert keinerlei Rückgabewert.

__Beispiel__: `ALMANAC_Update(12345);`

```php
string ALMANAC_DateInfo(int $InstanzID, int $Timestamp);
```

Gibt für das übergebene Datum (Unix Timestamp) alle Informationen als assoziatives Array zurück.
__HINWEIS:__ Das Datum sollte nur maximal +/- 1 Jahr vom aktuellen Tag entfernt liegen.

__Beispiel__: `ALMANAC_DateInfo(12345, strtotime('tomorrow'));`

> {  
> "IsSummer": true,  
> "IsLeapYear": true,  
> "IsWeekend": false,  
> "WeekNumber": 16,  
> "DaysInMonth": 30,  
> "DayOfYear": 108,  
> "WorkingDays": 20,  
> "Holiday": "Kein Feiertag",  
> "IsHoliday": false,  
> "SchoolHolidays": "Osterferien",  
> "IsSchoolHolidays": true  
}  

### 8. Versionshistorie

v3.2.20210126

* _FIX_: Quickfix wegen Sicherheitscheck bei Datenabholung

v3.1.20210116

* _NEU_: Funktion DateInfo liefert die Daten jetzt im JSON-Format
* _FIX_: Fehlerbehandlung komplett neu umgesetzt

v3.0.20210103

* _NEU_: Ermittlung der Ferien und Feiertage für DE, AT und CH
* _NEU_: Umstellung der Datenlieferung auf schulferien.org
* _FIX_: Name des Feiertages nicht korrekt gespeichert
* _FIX_: Vereinheitlichungen der Libs

v2.0.20200416

* _NEU_: Ermittlung der Arbeitstage im Monat
* _NEU_: Funktion DateInfo für manuelles Ermitteln der Daten für ein bestimmtes Datum
* _NEU_: Umstellung der Entwicklung auf Symcon StylePHP & Workflow actions

v1.2.20190813

* _NEU_: Anpassungen für Module Store
* _NEU_: Vereinheitlichungen, Umstellung auf Libs
* _NEU_: Lokalisierung (Englisch)

v1.1.20190501

* _FIX_: Name des Feiertages nicht korrekt gespeichert

v1.1.20190312

* _NEU_: Vereinheitlichungen, StyleCI uvm.

v1.0.20180505

* _FIX_: BugFix IPS 5.0

v1.0.20171230

* _NEU_: Initialversion

## Danksagung

Dieses Modul basiert auf der Idee und dem Modul von ...

* _Nall-chan_ : Modul _Schulferien_ <https://github.com/Nall-chan/IPSSchoolHolidays>

Vielen Dank für die hervorragende und tolle Arbeit!

## Entwickler

* Heiko Wilknitz ([@wilkware](https://github.com/wilkware))

## Spenden

Die Software ist für die nicht kommzerielle Nutzung kostenlos, Schenkungen als Unterstützung für den Entwickler bitte hier:

[![License](https://img.shields.io/badge/Einfach%20spenden%20mit-PayPal-blue.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

### Lizenz

[![Licence](https://licensebuttons.net/i/l/by-nc-sa/transparent/00/00/00/88x31-e.png)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
