# Datenmigration vom alten System

Dieses Dokument beschreibt, wie die Daten aus dem alten Ticketsystem (ticketsystem_db1) in das neue IT-Cockpit-System importiert werden.

## Voraussetzungen

- Die SQL-Dump-Dateien befinden sich in `docs/olddb/`:
  - `vlan_liste.sql` - Enthält die VLAN-Daten
  - `vlan_ip.sql` - Enthält die IP-Adressen-Daten

## Import-Methoden

### Methode 1: Artisan Command (Empfohlen)

Der einfachste Weg ist die Verwendung des bereitgestellten Artisan-Commands:

```bash
php artisan network:import-sql
```

Optional können Sie die Dateipfade angeben:

```bash
php artisan network:import-sql --vlans-file=docs/olddb/vlan_liste.sql --ips-file=docs/olddb/vlan_ip.sql
```

### Methode 2: Direkter MySQL Import

Alternativ können Sie die Daten direkt über MySQL importieren:

#### Schritt 1: VLANs importieren

```sql
-- Temporäre Tabelle erstellen
CREATE TEMPORARY TABLE temp_vlan_liste LIKE vlan_liste;

-- Daten aus SQL-Datei laden
SOURCE C:/xampp/htdocs/fscockpitV3/docs/olddb/vlan_liste.sql;

-- Daten in neue Struktur übertragen
INSERT INTO vlans (vlan_id, vlan_name, network_address, cidr_suffix, gateway, dhcp_from, dhcp_to, description, internes_netz, ipscan, created_at, updated_at)
SELECT 
    vlan_id,
    vlan_name,
    network_address,
    cidr_suffix,
    gateway,
    dhcp_from,
    dhcp_to,
    description,
    internes_netz,
    ipscan,
    NOW(),
    NOW()
FROM temp_vlan_liste
WHERE vlan_id != 999; -- Überspringe ungültige VLANs
```

#### Schritt 2: IP-Adressen importieren

```sql
-- Temporäre Tabelle erstellen
CREATE TEMPORARY TABLE temp_vlan_ip LIKE vlan_ip;

-- Daten aus SQL-Datei laden
SOURCE C:/xampp/htdocs/fscockpitV3/docs/olddb/vlan_ip.sql;

-- Daten in neue Struktur übertragen
INSERT INTO ip_addresses (vlan_id, ip_address, dns_name, mac_address, is_online, last_online_at, last_scanned_at, ping_ms, comment, created_at, updated_at)
SELECT 
    v.id as vlan_id,
    ti.ip_address,
    ti.dns_name,
    ti.mac_address,
    ti.is_online,
    ti.lastonline,
    ti.lasttest,
    ti.ping_response_time_ms,
    ti.kommentar,
    NOW(),
    NOW()
FROM temp_vlan_ip ti
INNER JOIN vlans v ON v.id = ti.vlan_liste_id;
```

## Datenstruktur-Mapping

### VLAN-Tabelle

| Alt (vlan_liste) | Neu (vlans) | Hinweise |
|------------------|-------------|----------|
| id | - | Wird nicht übernommen |
| vlan_id | vlan_id | Direkt übernommen |
| vlan_name | vlan_name | Direkt übernommen |
| network_address | network_address | Direkt übernommen |
| cidr_suffix | cidr_suffix | Direkt übernommen |
| gateway | gateway | Direkt übernommen |
| dhcp_from | dhcp_from | Direkt übernommen |
| dhcp_to | dhcp_to | Direkt übernommen |
| description | description | Direkt übernommen |
| internes_netz | internes_netz | Direkt übernommen |
| ipscan | ipscan | Direkt übernommen |
| - | scan_interval_minutes | Standardwert: 60 |
| - | last_scanned_at | NULL |

### IP-Adressen-Tabelle

| Alt (vlan_ip) | Neu (ip_addresses) | Hinweise |
|---------------|-------------------|----------|
| id | - | Wird nicht übernommen |
| vlan_liste_id | vlan_id | Mapping über VLAN-ID |
| dns_name | dns_name | Direkt übernommen |
| ip_address | ip_address | Direkt übernommen |
| mac_address | mac_address | Direkt übernommen |
| is_online | is_online | Direkt übernommen |
| lastonline | last_online_at | Umbenannt |
| lasttest | last_scanned_at | Umbenannt |
| ping_response_time_ms | ping_ms | Umbenannt |
| kommentar | comment | Umbenannt |
| ipscan | - | Wird nicht übernommen |

## Wichtige Hinweise

1. **VLAN-ID 999**: VLANs mit der ID 999 sind Platzhalter und sollten manuell überprüft werden
2. **Duplikate**: Der Import-Command überspringt automatisch bereits vorhandene Einträge
3. **Fehlerhafte Daten**: Einträge mit ungültigen Netzwerkadressen werden übersprungen
4. **Backup**: Erstellen Sie vor dem Import ein Backup der aktuellen Datenbank

## Überprüfung nach dem Import

Nach dem Import sollten Sie folgende Checks durchführen:

```bash
# Anzahl der importierten VLANs prüfen
php artisan tinker
>>> App\Modules\Network\Models\Vlan::count()

# Anzahl der importierten IP-Adressen prüfen
>>> App\Modules\Network\Models\IpAddress::count()

# VLANs mit Problemen finden
>>> App\Modules\Network\Models\Vlan::where('vlan_id', 999)->get()
```

## Fehlerbehebung

### Problem: "VLAN not found"
- Stellen Sie sicher, dass die VLANs vor den IP-Adressen importiert wurden
- Prüfen Sie, ob die vlan_liste_id in der alten Datenbank mit der neuen VLAN-ID übereinstimmt

### Problem: "Duplicate entry"
- Der Import überspringt automatisch Duplikate
- Wenn Sie neu importieren möchten, leeren Sie zuerst die Tabellen:
  ```bash
  php artisan migrate:fresh
  ```

### Problem: "File not found"
- Überprüfen Sie die Pfade zu den SQL-Dateien
- Stellen Sie sicher, dass die Dateien im Verzeichnis `docs/olddb/` liegen

## Support

Bei Problemen wenden Sie sich an das IT-Team oder erstellen Sie ein Issue im Repository.
