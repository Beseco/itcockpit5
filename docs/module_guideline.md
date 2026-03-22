# Entwickler-Richtlinie für IT-Cockpit Module

Diese Anleitung beschreibt, wie neue Funktionen (Module) entwickelt werden müssen, damit sie nahtlos in die Basis integriert werden können, ohne den Core-Code zu verändern.

## 1. Ordnerstruktur
Jedes Modul muss in einem eigenen Unterordner in `app/Modules/` liegen:

```text
/app/Modules/Inventory/
├── Providers/
│   └── InventoryServiceProvider.php  # Registrierung im System
├── Http/
│   └── Controllers/                  # Modul-Logik
├── Views/
│   ├── index.blade.php               # Hauptansicht
│   └── widget.blade.php              # Dashboard-Kachel
├── Routes/
│   └── web.php                       # Modul-Routen
└── module.json                       # Metadaten