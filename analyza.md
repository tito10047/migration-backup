# Analýza a Code Review projektu Migration Backup Bundle

Tento dokument obsahuje detailnú analýzu projektu `migration-backup-bundle`, identifikované chyby, zhodnotenie architektúry a návrhy na vylepšenie.

## 1. Celkové zhodnotenie
Projekt slúži ako Symfony bundle na automatické zálohovanie databázy pred spustením migrácií (`doctrine:migrations:migrate`). Je napísaný moderne (PHP 8.2+, Symfony 6.4-8.0), kód je čistý a dobre štruktúrovaný pomocou rozhraní a Dependency Injection. Avšak, obsahuje niekoľko kritických chýb v implementácii a architektonických nedostatkov, ktoré by mohli viesť k pádu aplikácie alebo zaplneniu disku.

---

## 2. Identifikované chyby a riziká

### ❌ Kritické: Riziko pretečenia pamäte (Memory Leak / OOM)
V súbore `src/Driver/MysqlBackupDriver.php` (riadok 40) sa výstup z príkazu `mysqldump` načítava do premennej PHP cez `$process->getOutput()` a následne ukladá do súboru cez `$this->fs->dumpFile()`.
- **Dôsledok:** Ak je databáza veľká (napr. 2GB), PHP sa pokúsi alokovať 2GB pamäte pre reťazec, čo pri bežnom `memory_limit` (napr. 128M - 512M) spôsobí pád procesu s chybou `Allowed memory size exhausted`.
- **Riešenie:** Použiť presmerovanie výstupu priamo do súboru (napr. cez `Process` a streamy alebo v rámci shell commandu).

### ❌ Kritické: Chýbajúci Cleanup dočasných súborov
`BackupManager` vytvára zálohu priamo v `backup_path`. Ak sa použije iný ako `LocalStorageProvider` (napr. v budúcnosti S3), súbor sa nahral do cieľa, ale jeho kópia v `backup_path` zostane navždy.
- **Dôsledok:** Postupné a nekontrolované zapĺňanie disku.
- **Riešenie:** Dumpovať do systémového `sys_get_temp_dir()` a po nahratí do `StorageProvider` súbor zmazať.

### ❌ Vysoké: Absencia Retention Policy (mazanie starých záloh)
Bundle neposkytuje žiadny mechanizmus na automatické odstraňovanie starých záloh.
- **Dôsledok:** Adresár so zálohami sa bude nekonečne zväčšovať, až kým nedôjde k zaplneniu disku.
- **Riešenie:** Pridať konfiguráciu `retention_days` alebo `max_backups` a po každej zálohe premazať staré súbory.

### ⚠️ Stredné: Architektonická duplicita cesty
Obe služby `BackupManager` aj `LocalStorageProvider` dostávajú tú istú konfiguráciu `backup_path`.
- **Dôsledok:** Nejasná zodpovednosť. `BackupManager` by nemal vedieť o finálnom umiestnení súborov, mal by vedieť len o dočasnom úložisku (temp). O finálnom uložení má rozhodovať `StorageProvider`.

### ⚠️ Nízke: Problém s pridávaním voľby `--backup`
V `CommandSubscriber` sa voľba `--backup` pridáva v evente `console.command`. To funguje pre `hasParameterOption`, ale Symfony Console už má v tomto momente spracované argumenty. Ak by iný listener chcel pristupovať k `$input->getOption('backup')`, mohol by naraziť na problémy.
- **Riešenie:** Pridať voľbu skôr (napr. cez `console.command` ale s vysokou prioritou, alebo cez `DefinitionConfigurator`).

---

## 3. Zhodnotenie štruktúry a návrh lepšej

Súčasná štruktúra je dobrá, ale trpí miešaním "dočasného" a "trvalého" úložiska.

### Aktuálna štruktúra:
- `Driver/`: MySQL, SQLite (OK)
- `Registry/`: Registry pre drivery (OK)
- `Storage/`: Provider pre úložisko (OK)
- `BackupManager`: Koordinátor (Mierne preťažený znalosťou ciest)

### Navrhovaná lepšia štruktúra:
1.  **Oddelenie Temp úložiska:** `BackupManager` dumpne dáta do `TemporaryFile`, ktorý sa po ukončení procesu (aj pri chybe) zmaže.
2.  **Jasná hierarchia Storage:** `StorageProvider` by mal byť jediný, kto vie o `backup_path`.
3.  **Command Filter:** Namiesto fixného príkazu v subscriberi umožniť v konfigurácii pole príkazov (regex alebo zoznam), ktoré majú spúšťať zálohu.

---

## 4. Testy: Pokrytie a kvalita

### Zistenia:
- ✅ Unit testy pre `BackupManager` sú prítomné a používajú mocky.
- ✅ Integračné testy používajú reálny `TestKernel` a testujú SQLite.
- ❌ **Edge case v testoch:** V `BackupManagerTest` sa porovnáva `date('Y-m-d-H-i-s')`. Ak test začne v jednej sekunde a `BackupManager` zavolá `date()` v nasledujúcej, test zlyhá.
- ❌ **Chýbajúce testy:**
    - Chýba test pre `MysqlBackupDriver` (aspoň s mockom procesu).
    - Chýba test pre `UnsupportedDatabaseException`.
    - Chýba test na zlyhanie úložiska (`StorageProvider` failure).

---

## 5. Návrhy na nové funkcie (Roadmap)

1.  **PostgreSQL Support:** Pridať `PostgresBackupDriver` (použitie `pg_dump`).
2.  **Retention Policy:** Možnosť nastaviť `keep_last_n_backups: 10`.
3.  **Compression:** Automatická kompresia dumpov do `.gz` alebo `.zip` (výrazne šetrí miesto).
4.  **S3 Storage Provider:** Implementácia úložiska pre AWS S3 alebo iné cloudové služby.
5.  **Exclude Tables:** Možnosť v konfigurácii definovať tabuľky, ktoré sa majú vynechať (napr. logy, cache).
6.  **Verify Command:** Príkaz na overenie integrity zálohy.
7.  **Restore Command:** Príkaz na rýchlu obnovu zo zálohy.
8.  **Asynchrónne zálohovanie:** Možnosť spustiť zálohu cez Messenger (aj keď pred migráciou je bezpečnejšie ju urobiť synchrónne).

---

## 6. Záver
Projekt je dobrým základom, ale pred nasadením do produkcie **je nevyhnutné opraviť narábanie s pamäťou v MySQL driveri**. Bez toho je bundle nepoužiteľný pre akékoľvek väčšie databázy. Následne odporúčam implementovať čistenie starých záloh, aby sa predišlo výpadkom kvôli zaplnenému disku.
