# Projektantrag: Break Your Bones 3D Game

---

## 1. Vision & Alleinstellungsmerkmal (USP)

*Break your Bones*

**USP**
* Entspannender Knochen break Mechanismus
* Chaotische Physics mit Ragdolls und und und...
* Babylon.js: Direkt im Browser spielbar ohne Ladezeiten

---

## 2. UI & UX | Die User-Perspektive

### Aufbau der Oberfläche
* **Hauptmenü:** 3D szene Mit Login und gespeicherten Daten
* **In-Game Overlay:**  Geld-Anzeige, Break Score, Aktions-Icons
* **Feedback-Elemente:** Satisfying sounds. Break Pop ups. Dopamin auschüttende Elemente.

### Inhalte & Interaktionsmöglichkeiten
* **Spielwelt:** Eine übersaturierte bunte welt mit Vielen Ragdoll möglichkeiten
* **Steuerung:** [z.B. WASD für Bewegung, Mausrad für Zoom, Linksklick für Interaktion]
* **Game Loop:** Geld Sammeln, Knochengelenke upgraden, rebirthen, Buffs Freischalften

---

## 3. Coder Plan | Die Entwickler-Perspektive

### Technologien
* **Engine:** Babylon.js
* **Physik:** vmtl. Havok Physics /Andere Babylon Klassen(Instanzen) für Kollisionen
* **Assets:**  Modelle im .glb/.gltf Format / Babylon-Parts. Roblox icons
* **Sprache:** TypeScript/JavaScript

### Technische Umsetzung
* **Scene Management:** Trennung von Loading-Scene und Game-Scene via SceneLoader
* **Kollisionslogik:** Babylons Physics Klassen verwenden um den Score zu errechenn 
* **Rendering:** Einsatz von VFX und Lighting settings ähnlich wie in Roblox alles hoch saturiert
* **Placement:** Vorarbeit in anderen 3D rendering Programmen um diese dann Einzufügen

### Grobe Datenstruktur (State Management)
| Objekt | Attribute | Zweck |
| :--- | :--- | :--- |
| **PlayerState** | name: varchar(20) password: varchar(20)gehashed wenn mögl, score: int, health: float, isAlive: bool | Tracking des aktuellen Spielzustands und Login ermöglichung |
---