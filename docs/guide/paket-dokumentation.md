# Paket-Dokumentation

Die Knowledge Base kann automatisch Markdown-Dokumentationen aus anderen Packages einbinden. Diese erscheinen als separate, schreibgeschuetzte Bereiche in der Sidebar (erkennbar am Schloss-Symbol).

## Dokumentation registrieren

Um Markdown-Dateien eines Packages in der Knowledge Base anzuzeigen, registrieren Sie diese im ServiceProvider:

```php
use TeamNiftyGmbH\NuxbeKnowledge\Support\KnowledgeManager;

public function boot(): void
{
    app(KnowledgeManager::class)->registerDocs(
        package: 'mein-paket',
        path: __DIR__ . '/../../docs',
        label: 'Mein Paket',
    );
}
```

### Parameter

- **package**: Eindeutiger Bezeichner fuer das Paket (z.B. `nuxbe-knowledge`)
- **path**: Pfad zum Verzeichnis mit den Markdown-Dateien
- **label**: Anzeigename in der Sidebar
- **icon**: Optionales Icon (Standard: `book-open`)

## Verzeichnisstruktur

Das registrierte Verzeichnis wird rekursiv nach `.md` Dateien durchsucht. Unterverzeichnisse werden als aufklappbare Gruppen dargestellt.

```
docs/
  uebersicht.md
  artikel-verwalten.md
  unterverzeichnis/
    weitere-docs.md
```

Dateinamen werden automatisch formatiert: Bindestriche und Unterstriche werden durch Leerzeichen ersetzt, der erste Buchstabe wird grossgeschrieben.

## Markdown-Rendering

Dokumentationen werden mit GitHub Flavored Markdown gerendert. Unterstuetzte Elemente:

- Ueberschriften, Absaetze, Listen
- Code-Bloecke mit Syntax-Highlighting
- Tabellen
- Links (nur sichere Protokolle)

HTML-Tags im Markdown werden aus Sicherheitsgruenden entfernt.
