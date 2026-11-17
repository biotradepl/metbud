# METBUD Production — projekt strony www

## Repozytorium
- **GitHub:** biotradepl/metbud
- **Branch roboczy:** `claude/check-index-projects-08nTg`
- **Produkcja:** https://www.metbud.net

---

## Struktura plików

```
/
├── index.html              # Strona główna
├── projekty/
│   └── index.html          # Podstrona projektów UE (dofinansowania)
├── mail.php                # Skrypt PHP obsługujący formularz kontaktowy
├── Metbud_biale_poziome_RED_accent.png  # Logo (białe, poziome)
├── img_laweta_hero.jpg     # Zdjęcie hero — laweta z blachą
├── img_laweta_blach.jpg    # Sekcja lawety
├── img_drabina_silo.jpg    # Sekcja szczeble/drabiny
├── img_szczeble_boot.jpg   # Sekcja szczeble
├── ogrodzenie.jpeg         # Sekcja balustrady/ogrodzenia
└── Zrzut-ekranu-2021-03-22-o-12.16.14-1024x197.png  # Logotypy UE (na podstronie projekty)
```

---

## Hosting i infrastruktura

- **Hosting:** Cyberfolks (PHP dostępny)
- **Domena:** metbud.net
- **Poczta:** biuro@metbud.net — skrzynka **współdzielona Microsoft 365**, MX/SPF/DKIM wskazują na Microsoft
- **Analytics:** Google Analytics G-5EXLF744RL (tag w `<head>` index.html)

### Znany problem z formularzem
PHP `mail()` nie działa bo domena jest autoryzowana tylko przez Microsoft.
**Do rozwiązania:** dodać Cyberfolks do rekordu SPF i założyć skrzynkę `formularz@metbud.net` na hostingu (tylko do wysyłania), albo użyć Microsoft Graph API. Aktualnie formularz wysyła fetch do `mail.php` i wyświetla błąd.

---

## Oferta / segmenty klientów (B2B)

| Sekcja | ID | Klienci docelowi |
|---|---|---|
| Komponenty do lawet i autotransporterów | `#lawety` | Producenci lawet, autotransporterów, platform LOHR, serwisy lawetowe |
| Szczeble drabinowe i podesty | `#szczeble` | Producenci drabin pionowych, klatek schodowych, zbiorników, silosów |
| Balustrady, ogrodzenia, schody, wiaty | `#balustrady` | Deweloperzy, generalni wykonawcy |

**Propozycja wartości:** produkcja na wymiar, wycena 48h, Polska produkcja, dostawa UE, 15+ lat, 500+ klientów B2B.

---

## Co zostało zrobione (sesja 2026-03)

### Strona główna (`index.html`)
- Przebudowa strony — 3 sekcje ofertowe, editorial design (Barlow Condensed, dark theme)
- Intro overlay z animowanym logo
- Ticker z informacjami o produktach
- Hero z animowanym tytułem + zdjęciem
- 3 sekcje ofertowe z listami produktów i zdjęciami
- Sekcja kontaktowa z formularzem + mapa Google z kartą firmową
- Przycisk "Prowadź do nas" na mapie
- Cookie bar + modal RODO
- Google Analytics G-5EXLF744RL
- Naprawiony formularz: atrybuty `name` na polach, fetch do `mail.php`

### Poprawki UI
- Logo desktop zmniejszone (76px → 54px), pasek nawigacyjny bez zmian (88px)
- Logo mobile zmniejszone (do 48px przy <640px)
- Hamburger mobile powiększony (padding, grubość kresek)
- Ticker: margin-top poprawiony (60px → 88px = wysokość nav)
- Hero: grafika przywrócona na mobile jako baner 260px (order:-1)
- `scroll-padding-top: 88px` na html — linki kotwicowe nie przykryte nawem
- Mapa: iframe zaktualizowany do nazwy firmy (dokładniejsza pinezka)

### Podstrona projektów UE (`projekty/index.html`)
- Logo nav zmniejszone (76px → 46px, pasuje do nav 60px)
- Logotypy EU: `width:100% height:auto` — wypełniają cały biały box

---

## Do zrobienia (backlog)

### Priorytet wysoki
- [ ] **Formularz** — naprawić wysyłkę maila (SPF + skrzynka Cyberfolks LUB Microsoft Graph API)
- [ ] **Zdjęcia** — dodać nowe zdjęcia produktów (lawety, szczeble, balustrady, realizacje)

### Priorytet średni
- [ ] **Wersje językowe** — dodać EN (i opcjonalnie DE/DK — rynek duński i francuski z projektów UE)
- [ ] **Redesign** — odświeżenie wyglądu z zachowaniem obecnego formularza i struktury sekcji
- [ ] **Podstrona projekty** — dopasować nav do wyglądu strony głównej (aktualnie osobny, prostszy styl)

### Priorytet niski
- [ ] Przenieść `mail.php` i inne niezmieniane pliki do repo (teraz tylko na serwerze)
- [ ] Optymalizacja obrazów (pliki `hf_*` ważą po kilka MB)
- [ ] SEO: meta tagi, sitemap, robots.txt

---

## Technologia

- Czysty HTML/CSS/JS — bez frameworka, bez buildu
- Fonty: Barlow + Barlow Condensed (Google Fonts)
- Mapa: Google Maps embed (iframe)
- Formularz backend: PHP `mail()` / docelowo PHPMailer z SMTP
- Animacje: CSS keyframes + IntersectionObserver
