# Számlázz.hu modul a Commerce-core rendszerhez

## Leírás
Ez a modul lehetővé teszi a Számlázz.hu Számla Agent API-ján keresztüli bizonylatkezelést (számla kiállítás, érvénytelenítés, PDF letöltés stb.) a `weboldalnet/commerce-core` alapú webshopokhoz.

## Telepítés
1. Publikáld a konfigurációt és a migrációkat:
   ```bash
   php artisan vendor:publish --tag=commerce-szamlazzhu-all
   ```
2. Futtasd a migrációkat:
   ```bash
   php artisan migrate
   ```
3. Regisztráld a nézet kiegészítéseket (sidebar):
   ```bash
   php artisan commerce-szamlazzhu:install
   php artisan commerce-szamlazzhu:extend
   ```

## Beállítások
Az admin felületen a **Webshop > Számlázz.hu** menüpont alatt adhatók meg az API eléréséhez szükséges adatok (Agent kulcs vagy felhasználónév/jelszó).

### Működési módok
- **Manuális:** Csak az adminisztrátor által kezdeményezett számlázás történik.
- **Automatikus:** Sikeres online fizetés után a rendszer automatikusan kiállítja a számlát.

## Támogatott funkciók
- Számla kiállítása (E-számla és papír alapú)
- Számla érvénytelenítése
- PDF letöltése és helyi tárolása
- Automatikus számlázás online fizetéskor
- Kapcsolat tesztelése

## Tárolás
A generált fájlok (PDF, XML, Log) a `storage/app/private/commerce-szamlazzhu` mappába kerülnek. A PDF-ek elérése hitelesített admin route-on keresztül történik a biztonság érdekében.
