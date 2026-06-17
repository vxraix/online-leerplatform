# Functionele Eisen - Online Leerplatform

**Versie:** 1.0  
**Datum:** 2024  
**Status:** Concept op basis van klantinterview

---

## 1. CURSUSBEHEER

### Cursus Categorieën
- Alle cursussen moeten ICT-gerelateerd zijn
- Cursussen moeten binnen het ICT-domein vallen (programmeren, netwerken, databases, etc.)
- Prioriteit: **Hoog**

### Cursus Eigenschappen
- Cursus titel, beschrijving, instructeur
- Duur (uren)
- Prijs
- Status (actief/inactief)
- Cursus categorie (verplicht ICT-gerelateerd)
- Cursus niveau (beginner, gevorderd, expert)

### Cursus Relaties
- Eén abonnement (account) kan meerdere cursussen volgen
- Een gebruiker met een actief abonnement kan zich inschrijven voor meerdere cursussen
- Prioriteit: **Hoog**

---

## 2. GEBRUIKERS & ROLLEN

### Gebruikersrollen

**Admin:**
- Heeft alle rechten
- Kan alles beheren (gebruikers, cursussen, abonnementen, inschrijvingen, repetities, cijfers)
- Ziet volledig dashboard met alle statistieken

**Leerkracht/Docent:**
- Kan cursussen volgen (zoals student)
- Kan cijfers geven aan studenten
- Kan repetities aanmaken en beheren
- Ziet dashboard met: vakken, klassen, cijfers
- Ziet rooster met repetities

**Student:**
- Kan zichzelf registreren
- Kan cursussen volgen (met actief abonnement)
- Ziet eigen cijfers en gegevens
- Ziet rooster met repetities
- Kan feedback/klachten indienen

### Registratie
- Studenten kunnen zichzelf registreren
- Admin kan ook gebruikers aanmaken
- Prioriteit: **Hoog**

---

## 3. ABONNEMENTEN

### Abonnement Model
- Eén gebruiker heeft één abonnement (account)
- Met één abonnement kunnen meerdere cursussen gevolgd worden
- Abonnement geeft toegang tot het platform, niet per cursus
- Prioriteit: **Hoog**

### Abonnement Eigenschappen
- Type (Premium, Basic, Pro, etc.)
- Startdatum
- Einddatum (of onbeperkt)
- Status (actief/inactief)

### Automatische Beëindiging
- Wanneer school is afgerond, wordt gebruiker automatisch uitgelogd
- Meldingen van tevoren wanneer abonnement afloopt
- X dagen voor afloop: waarschuwing
- Op einddatum: automatisch uitloggen en account deactiveren
- Prioriteit: **Hoog**

---

## 4. REPETITIES (TOETSEN/EXAMENS)

### Repetitie Functionaliteit
- Repetities kunnen worden aangemaakt
- Repetities hebben datum en tijd
- Automatisch stoppen wanneer de tijd is bereikt
- Repetities zijn aparte entiteiten naast cursussen
- Hebben starttijd en eindtijd
- Timer die automatisch stopt bij eindtijd
- Prioriteit: **Hoog**

### Repetitie Eigenschappen
- Titel
- Beschrijving
- Cursus (koppeling)
- Klas/Groep
- Datum
- Starttijd
- Eindtijd
- Duur (automatisch berekend)
- Status (gepland, actief, afgerond)
- Automatische timer functionaliteit

### Repetitie Beheer
- Docent kan repetities aanmaken
- Docent kan repetities bewerken
- Docent kan repetities verwijderen
- Admin kan alle repetities beheren

---

## 5. CIJFERS & CIJFERLIJST

### Cijfer Functionaliteit
- Leerkracht kan cijfers geven aan studenten
- Cijferlijst moet klas gegevens tonen
- Welke klas/student
- Welke cursus/vak
- Welke repetitie/toets
- Cijfer
- Datum
- Opmerkingen
- Prioriteit: **Hoog**

### Cijferlijst Gegevens
- Student naam
- Student nummer/ID
- Klas/Groep
- Cursus/Vak naam
- Repetitie/Toets naam
- Cijfer (cijfer of beoordeling)
- Datum
- Gewicht (optioneel)
- Opmerkingen

### Cijfer Overzicht
- Student Dashboard: Ziet eigen cijfers per cursus
- Docent Dashboard: Ziet cijfers van alle studenten in hun klassen
- Admin Dashboard: Ziet alle cijfers van alle studenten

---

## 6. ROOSTER

### Rooster Functionaliteit
- Bij inloggen moet rooster zichtbaar zijn
- Toont komende repetities met datum en tijd
- Overzicht van geplande repetities
- Sorteerbaar op datum
- Filterbaar op cursus/klas
- Prioriteit: **Hoog**

### Rooster Weergave
- Datum
- Tijd (start en eind)
- Cursus/Vak
- Klas/Groep
- Locatie (optioneel)
- Status (komend, bezig, afgerond)

### Rooster per Rol
- Student: Ziet eigen rooster met repetities waar hij/zij voor ingeschreven is
- Docent: Ziet rooster met alle repetities die hij/zij geeft
- Admin: Ziet volledig rooster van alle repetities

---

## 7. KLASSEN & GROEPEN

### Klas Functionaliteit
- Klassen moeten kunnen worden beheerd
- Studenten moeten aan klassen gekoppeld kunnen worden
- Klassen/groepen aanmaken
- Studenten toewijzen aan klassen
- Docenten toewijzen aan klassen
- Prioriteit: **Hoog**

### Klas Eigenschappen
- Klas naam
- Klas code
- Studiejaar
- Docent(en)
- Studenten lijst
- Cursussen die gevolgd worden

### Klas Beheer
- Admin kan klassen aanmaken
- Admin kan studenten toewijzen aan klassen
- Admin kan docenten toewijzen aan klassen
- Docent ziet zijn/haar klassen in dashboard

---

## 8. DASHBOARD PER ROL

### Student Dashboard
- Toont cijfers en gegevens van de student
- Overzicht eigen cijfers (per cursus)
- Gemiddelde cijfers
- Ingeschreven cursussen
- Komende repetities (rooster)
- Abonnement status
- Recente activiteit

### Docent Dashboard
- Toont vakken en cijfers
- Ziet verschillende categorieën: klassen, cijfers, etc.
- Overzicht eigen klassen
- Overzicht eigen cursussen/vakken
- Cijfers van studenten (per klas)
- Komende repetities (rooster)
- Statistieken (gemiddelde cijfers per klas)

### Admin Dashboard
- Ziet alles
- Statistieken (gebruikers, cursussen, abonnementen, inschrijvingen)
- Overzicht alle klassen
- Overzicht alle cijfers
- Overzicht alle repetities
- Recente activiteit

---

## 9. ZOEK & FILTER

### Zoek Functionaliteit
- Zoek en filterfunctie moet aanwezig zijn
- Zoeken in cursussen (titel, beschrijving, instructeur)
- Zoeken in gebruikers (naam, email, gebruikersnaam)
- Zoeken in repetities (titel, cursus, datum)
- Zoeken in cijfers (student, cursus)
- Prioriteit: **Hoog**

### Filter Functionaliteit
- Filter op rol (admin, docent, student)
- Filter op status (actief/inactief)
- Filter op datum (repetities, cijfers)
- Filter op klas
- Filter op cursus

---

## 10. FEEDBACK & KLACHTEN

### Feedback Functionaliteit
- Pagina waar studenten klachten kunnen geven
- Studenten kunnen feedback/klachten indienen
- Feedback heeft categorie (technisch, inhoudelijk, algemeen)
- Feedback heeft status (nieuw, in behandeling, afgehandeld)
- Admin kan feedback bekijken en beheren
- Prioriteit: **Hoog**

### Feedback Eigenschappen
- Titel
- Beschrijving
- Categorie
- Prioriteit (laag, normaal, hoog)
- Status
- Datum indiening
- Reactie van admin (optioneel)

### Feedback Beheer
- Student kan eigen feedback bekijken
- Admin kan alle feedback bekijken
- Admin kan feedback beantwoorden
- Admin kan feedback status wijzigen

---

## 11. BEVEILIGING

### Authenticatie
- Beveiliging moet top zijn
- Login functionaliteit
- Wachtwoord hashing (bcrypt)
- Session management
- Automatisch uitloggen bij inactiviteit (optioneel)

### Autorisatie
- Admin heeft alle rechten
- Rolgebaseerde toegang (RBAC)
- Pagina toegangscontrole
- API/actie toegangscontrole

### Data Beveiliging
- Input validatie (server-side)
- SQL injection preventie (prepared statements)
- XSS preventie (output escaping)
- CSRF protectie

---

## 12. USER EXPERIENCE

### UX Prioriteit
- User experience is top priority
- Intuïtieve navigatie
- Duidelijke feedback bij acties
- Responsive design (mobiel, tablet, desktop)
- Snelle laadtijden
- Toegankelijkheid (WCAG richtlijnen)
- Prioriteit: **Hoog**

### UI/UX Eisen
- Moderne, schone interface
- Consistente styling
- Duidelijke call-to-actions
- Foutmeldingen zijn duidelijk en behulpzaam
- Succesmeldingen bij acties
- Loading states bij lange operaties
- Bevestigingsdialogen bij belangrijke acties

---

## 13. AUTOMATISERINGEN

### Automatische Acties
- Automatisch stoppen van repetitie wanneer tijd bereikt
- Automatisch uitloggen wanneer school afgerond
- Meldingen van tevoren bij aflopend abonnement
- Timer voor repetities
- Cron job voor abonnement controle
- Email/notificatie systeem
- Prioriteit: **Hoog**

---

## 14. TECHNISCHE EISEN

### Database
- MySQL/MariaDB
- Goede relaties tussen tabellen
- Indexering voor performance
- Foreign keys voor data integriteit

### Backend
- PHP 7.4+
- PDO voor database connecties
- Prepared statements
- Server-side validatie

### Frontend
- Bootstrap 5 voor responsive design
- JavaScript voor interactiviteit
- AJAX waar nodig voor betere UX

---

## 15. PRIORITEITEN OVERZICHT

### Hoog (Must Have)
- Cursussen ICT-gerelateerd
- Eén abonnement, meerdere cursussen
- Leerkracht kan cijfers geven
- Cijferlijst met klas gegevens
- Rooster met repetities (datum en tijd)
- Repetities kunnen aangemaakt worden
- Automatisch stoppen repetitie bij tijd
- Automatisch uitloggen bij afgeronde school
- Meldingen voor aflopend abonnement
- Dashboard per rol (student, docent, admin)
- Zoek en filter functionaliteit
- Beveiliging
- Feedback/klachten pagina
- User experience

### Medium (Should Have)
- Automatische timer voor repetities
- Email notificaties
- Geavanceerde statistieken
- Export functionaliteit (cijfers, rooster)

### Laag (Nice to Have)
- Mobile app
- Push notificaties
- Chat functionaliteit
- Bestand uploads voor opdrachten

---

## 16. NIEUWE ENTITEITEN

### Nieuwe Database Tabellen
- **klassen** - Klas/groep informatie
- **repetities** - Toetsen/examens
- **cijfers** - Cijfers van studenten
- **feedback** - Feedback/klachten van studenten
- **notificaties** - Systeem notificaties

### Aanpassingen Bestaande Tabellen
- **users** - Toevoegen: klas_id, student_nummer
- **courses** - Toevoegen: categorie (ICT), niveau
- **subscriptions** - Toevoegen: einddatum school, notificatie_datum

---

## 17. WIJZIGINGEN BESTAANDE FUNCTIONALITEIT

### Cursusbeheer
- Cursussen moeten ICT-gerelateerd zijn (validatie)
- Categorie veld toevoegen

### Gebruikersbeheer
- Rol "docent" toevoegen (naast admin en student)
- Klas toewijzing functionaliteit
- Student nummer veld

### Abonnementen
- Logica voor automatisch uitloggen bij einddatum
- Notificatie systeem voor aflopende abonnementen

### Dashboard
- Aanpassen per rol (student, docent, admin)
- Rooster integratie
- Cijfers overzicht

---

## CONCLUSIE

De belangrijkste toevoegingen zijn:
- Repetities systeem
- Cijfers beheer
- Klassen beheer
- Rooster functionaliteit
- Feedback systeem
- Automatische acties (timers, uitloggen)
- Uitgebreide dashboard per rol

**Volgende stap:** Technisch ontwerp en database schema uitbreiding.
