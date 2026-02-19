# local_mycoursesbycategory — Piano di lavoro

## Obiettivo

Creare un plugin Moodle `local_mycoursesbycategory` che sostituisca la pagina standard "I miei corsi" (`/my/courses.php`) con una vista che raggruppa i corsi iscritti dell'utente per **categoria**, con sezioni collassabili, card dei corsi con immagine e barra di progresso.

**Cliente**: Polis Lombardia  
**Compatibilità**: Moodle 4.3, 4.4, 4.5, 5.0 | PHP 8.1–8.3  
**Lingue**: Italiano (default), English

---

## Architettura

```
local/mycoursesbycategory/
├── classes/
│   ├── output/
│   │   └── renderer.php              # Custom renderer
│   └── external/
│       └── get_courses_by_category.php  # (opzionale) Webservice per AJAX
├── templates/
│   ├── main.mustache                  # Layout principale con categorie
│   ├── category_section.mustache      # Singola sezione categoria collassabile
│   └── course_card.mustache           # Card singolo corso
├── db/
│   ├── access.php                     # Capabilities
│   └── services.php                   # (opzionale) Registrazione webservice
├── lang/
│   ├── en/
│   │   └── local_mycoursesbycategory.php
│   └── it/
│       └── local_mycoursesbycategory.php
├── amd/
│   └── src/
│       └── collapse.js                # JS per toggle sezioni e preferenze utente
├── pix/
│   └── icon.svg                       # Icona plugin
├── index.php                          # Pagina principale
├── lib.php                            # Callbacks (redirect, navigation)
├── settings.php                       # Impostazioni admin
├── version.php                        # Versione e dipendenze
├── WORKPLAN.md                        # Questo file
└── README.md                          # Documentazione utente
```

---

## Fasi di sviluppo

### Fase 1 — Scaffolding base del plugin

**File da creare**: `version.php`, `lang/en/`, `lang/it/`, `db/access.php`

1. Creare `version.php` con:
   - `$plugin->component = 'local_mycoursesbycategory'`
   - `$plugin->version` con data corrente nel formato YYYYMMDD00
   - `$plugin->requires` = Moodle 4.3 (2023100900)
   - `$plugin->maturity = MATURITY_STABLE`
   - `$plugin->release = '1.0.0'`

2. Creare `db/access.php` con capability:
   - `local/mycoursesbycategory:view` — assegnata di default ai ruoli `user` e `student`

3. Creare file lingua:
   - `lang/en/local_mycoursesbycategory.php` con tutte le stringhe in inglese
   - `lang/it/local_mycoursesbycategory.php` con tutte le stringhe in italiano
   - Stringhe necessarie: `pluginname`, `mycoursesbycategory`, `nocourses`, `courses`, `category`, `allcategories`, `collapsall`, `expandall`, `progress`, `gotocourse`, `redirectenabled`, `redirectdescription`, `showprogress`, `showprogressdescription`, `courseimage_default`, `nocourseimage`

### Fase 2 — Pagina principale (`index.php`)

Creare `index.php` che:

1. Richiede login (`require_login()`)
2. Verifica capability `local/mycoursesbycategory:view`
3. Imposta `$PAGE` con:
   - `context = context_system::instance()`
   - `url = /local/mycoursesbycategory/index.php`
   - `pagelayout = 'mycourses'`
   - `title` e `heading` appropriati
4. Recupera i corsi con `enrol_get_my_courses()` passando i campi necessari inclusi `id, fullname, shortname, category, visible, enddate`
5. Per ogni corso:
   - Recupera la categoria con `core_course_category::get()`
   - Recupera l'immagine del corso tramite `course_get_course_image()` o fallback a immagine placeholder
   - Calcola il progresso con `\core_completion\progress::get_course_progress_percentage()`
   - Costruisce URL corso con `new moodle_url('/course/view.php', ['id' => $course->id])`
6. Raggruppa i corsi in un array di categorie, ordinato alfabeticamente per nome categoria
7. Passa i dati al renderer

**Logica di raggruppamento** (da inserire in una classe helper `classes/helper.php`):

```php
public static function get_courses_grouped_by_category(int $userid = 0): array {
    global $USER;
    if (!$userid) {
        $userid = $USER->id;
    }
    
    $courses = enrol_get_my_courses('*', 'fullname ASC', 0, [], false, $userid);
    $categories = [];
    
    foreach ($courses as $course) {
        if ($course->id == SITEID) {
            continue;
        }
        $cat = core_course_category::get($course->category, IGNORE_MISSING);
        $catname = $cat ? $cat->get_formatted_name() : get_string('miscellaneous');
        $catid = $course->category;
        
        if (!isset($categories[$catid])) {
            $categories[$catid] = [
                'id' => $catid,
                'name' => $catname,
                'courses' => [],
                'coursecount' => 0,
            ];
        }
        
        // Immagine del corso.
        $courseobj = new core_course_list_element($course);
        $courseimage = self::get_course_image($courseobj);
        
        // Progresso.
        $progress = \core_completion\progress::get_course_progress_percentage($course, $userid);
        
        $categories[$catid]['courses'][] = [
            'id' => $course->id,
            'fullname' => format_string($course->fullname, true),
            'shortname' => format_string($course->shortname, true),
            'courseurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'courseimage' => $courseimage,
            'progress' => $progress !== null ? round($progress) : null,
            'hasprogress' => $progress !== null,
        ];
        $categories[$catid]['coursecount']++;
    }
    
    // Ordina categorie per nome.
    usort($categories, fn($a, $b) => strcmp($a['name'], $b['name']));
    
    return $categories;
}
```

### Fase 3 — Template Mustache

#### `templates/main.mustache`

Template principale che:
- Mostra un header con titolo pagina e pulsanti "Espandi tutto" / "Comprimi tutto"
- Mostra il conteggio totale dei corsi
- Itera sulle categorie e include `category_section` per ciascuna
- Mostra un messaggio se l'utente non ha corsi

```mustache
<div id="local-mycoursesbycategory" class="local-mycoursesbycategory-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>{{title}}</h2>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-secondary" data-action="expand-all">
                {{#str}}expandall, local_mycoursesbycategory{{/str}}
            </button>
            <button class="btn btn-sm btn-outline-secondary" data-action="collapse-all">
                {{#str}}collapseall, local_mycoursesbycategory{{/str}}
            </button>
        </div>
    </div>

    {{^hascourses}}
    <div class="alert alert-info">{{#str}}nocourses, local_mycoursesbycategory{{/str}}</div>
    {{/hascourses}}

    {{#categories}}
        {{> local_mycoursesbycategory/category_section}}
    {{/categories}}
</div>
```

#### `templates/category_section.mustache`

Sezione singola categoria:
- Header cliccabile con nome categoria e badge conteggio corsi
- Corpo collassabile (Bootstrap collapse, aperto di default)
- Grid responsive di course card

```mustache
<div class="card mb-3 category-section">
    <div class="card-header d-flex justify-content-between align-items-center"
         role="button" data-toggle="collapse" data-target="#category-{{id}}"
         aria-expanded="true" aria-controls="category-{{id}}">
        <h4 class="mb-0">
            <i class="fa fa-folder-open mr-2"></i>{{name}}
        </h4>
        <span class="badge badge-pill badge-primary">{{coursecount}}</span>
    </div>
    <div id="category-{{id}}" class="collapse show">
        <div class="card-body">
            <div class="row">
                {{#courses}}
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-3">
                        {{> local_mycoursesbycategory/course_card}}
                    </div>
                {{/courses}}
            </div>
        </div>
    </div>
</div>
```

#### `templates/course_card.mustache`

Card singolo corso con:
- Immagine corso (o placeholder)
- Titolo con link
- Barra di progresso (se completion abilitata)
- Badge progresso percentuale

```mustache
<div class="card h-100 course-card shadow-sm">
    {{#courseimage}}
    <a href="{{courseurl}}">
        <img src="{{courseimage}}" class="card-img-top" alt="{{fullname}}" 
             style="height: 140px; object-fit: cover;">
    </a>
    {{/courseimage}}
    {{^courseimage}}
    <a href="{{courseurl}}">
        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center"
             style="height: 140px;">
            <i class="fa fa-graduation-cap fa-3x text-white"></i>
        </div>
    </a>
    {{/courseimage}}
    <div class="card-body d-flex flex-column">
        <h5 class="card-title">
            <a href="{{courseurl}}" class="text-dark">{{fullname}}</a>
        </h5>
        {{#hasprogress}}
        <div class="mt-auto">
            <div class="d-flex justify-content-between mb-1">
                <small class="text-muted">{{#str}}progress, local_mycoursesbycategory{{/str}}</small>
                <small class="font-weight-bold">{{progress}}%</small>
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: {{progress}}%" aria-valuenow="{{progress}}" 
                     aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
        {{/hasprogress}}
    </div>
</div>
```

### Fase 4 — Impostazioni admin e redirect

#### `settings.php`

Aggiungere la pagina di impostazioni sotto "Plugin locali" con:
- **Abilita redirect** (`local_mycoursesbycategory/enableredirect`): checkbox, default off
- **Mostra progresso** (`local_mycoursesbycategory/showprogress`): checkbox, default on
- **Layout**: scelta tra `card` e `list` (per futura estensione)

#### `lib.php`

Implementare il callback `local_mycoursesbycategory_before_http_headers()` per il redirect:

```php
function local_mycoursesbycategory_before_http_headers() {
    global $PAGE, $CFG;
    
    if (!get_config('local_mycoursesbycategory', 'enableredirect')) {
        return;
    }
    
    $requesturi = $_SERVER['REQUEST_URI'] ?? '';
    // Intercetta /my/courses.php
    if (strpos($requesturi, '/my/courses.php') !== false) {
        redirect(new moodle_url('/local/mycoursesbycategory/index.php'));
    }
}
```

Implementare `local_mycoursesbycategory_extend_navigation()` per aggiungere un link nella navigazione:

```php
function local_mycoursesbycategory_extend_navigation(global_navigation $navigation) {
    $mycourses = $navigation->find('mycourses', navigation_node::TYPE_ROOTNODE);
    if ($mycourses) {
        $mycourses->add(
            get_string('mycoursesbycategory', 'local_mycoursesbycategory'),
            new moodle_url('/local/mycoursesbycategory/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'mycoursesbycategory',
            new pix_icon('i/course', '')
        );
    }
}
```

### Fase 5 — JavaScript (AMD module)

#### `amd/src/collapse.js`

Modulo AMD per:
- Gestione pulsanti "Espandi tutto" / "Comprimi tutto"
- Salvataggio preferenze di collasso per utente via `core/user` preferences (opzionale) o `sessionStorage`
- Animazioni smooth per toggle sezioni

```javascript
define(['jquery'], function($) {
    return {
        init: function() {
            var container = $('#local-mycoursesbycategory');
            
            container.on('click', '[data-action="expand-all"]', function() {
                container.find('.collapse').collapse('show');
            });
            
            container.on('click', '[data-action="collapse-all"]', function() {
                container.find('.collapse').collapse('hide');
            });
        }
    };
});
```

Nota: compilare con `npx grunt amd` per generare il file minificato in `amd/build/`.

### Fase 6 — Stili CSS

#### `styles.css` (nella root del plugin)

```css
.local-mycoursesbycategory-container .category-section .card-header {
    cursor: pointer;
    transition: background-color 0.2s;
}
.local-mycoursesbycategory-container .category-section .card-header:hover {
    background-color: rgba(0,0,0,0.05);
}
.local-mycoursesbycategory-container .course-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.local-mycoursesbycategory-container .course-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.local-mycoursesbycategory-container .card-header[aria-expanded="false"] .fa-folder-open:before {
    content: "\f07b"; /* fa-folder (chiusa) */
}
```

### Fase 7 — CI e validazione

1. Creare `.github/workflows/ci.yml` con moodle-plugin-ci:
   - PHP: 8.1, 8.2, 8.3
   - Moodle: MOODLE_403_STABLE, MOODLE_404_STABLE, MOODLE_405_STABLE, MOODLE_500_STABLE
   - Database: pgsql, mariadb
   - Jobs: phplint, phpcs (moodle standard), phpdoc, grunt, phpunit, behat

2. Creare `tests/helper_test.php` con test PHPUnit per:
   - `get_courses_grouped_by_category()` con corsi in categorie diverse
   - Corsi senza categoria (fallback)
   - Utente senza corsi (array vuoto)

3. Verificare che `phpcs --standard=moodle` passi senza errori

### Fase 8 — README e documentazione

Creare `README.md` con:
- Descrizione del plugin
- Requisiti
- Installazione (manuale e via git)
- Configurazione (impostazioni admin)
- Screenshot placeholder
- Licenza GPLv3
- Crediti Invisiblefarm

---

## Checklist finale

- [ ] `version.php` corretto con requires e dependencies
- [ ] `db/access.php` con capabilities
- [ ] `lang/en/` e `lang/it/` completi (tutte le stringhe usate nei template e in PHP)
- [ ] `index.php` funzionante con raggruppamento per categoria
- [ ] `classes/helper.php` con logica di raggruppamento testata
- [ ] 3 template Mustache: `main.mustache`, `category_section.mustache`, `course_card.mustache`
- [ ] `settings.php` con opzione redirect e mostra progresso
- [ ] `lib.php` con redirect condizionale e navigazione
- [ ] `amd/src/collapse.js` compilato
- [ ] `styles.css` con stili card e hover
- [ ] `.github/workflows/ci.yml` configurato
- [ ] `tests/helper_test.php` con almeno 3 test case
- [ ] `README.md` completo
- [ ] phpcs clean (moodle standard)
- [ ] Nessun warning phpdoc

---

## Note per Claude Code

- Seguire lo standard di codifica Moodle: https://moodledev.io/general/development/policies/codingstyle
- Usare `MOODLE_INTERNAL` check in tutti i file PHP tranne `index.php`
- Usare `required_param()` / `optional_param()` per tutti i parametri HTTP
- Tutti i template devono usare `{{#str}}` per le stringhe, MAI hardcodare testo
- Le classi vanno in `classes/` con autoload PSR-4 (namespace `local_mycoursesbycategory`)
- I file `lib.php` devono contenere solo le funzioni callback con prefisso `local_mycoursesbycategory_`
- Testare che il plugin si installi correttamente su Moodle 4.3 minimo
- Il file `index.php` deve iniziare con `<?php` seguito dal commento di licenza GPLv3 standard Moodle
