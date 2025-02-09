# Serwis aplikacji

## 1. Definicja i Rola Serwisów Aplikacji
Serwisy aplikacji służą do wykonywania określonej logiki biznesowej w systemie. Ich główną rolą jest koordynowanie operacji na poziomie aplikacji, zapewniając spójność i separację odpowiedzialności zgodnie z zasadami SOLID. 

Serwisy aplikacji:
- Pośredniczą między warstwą kontrolera a warstwą domeny.
- Nie implementują szczegółowej logiki domenowej (za to odpowiadają serwisy domenowe).
- Utrzymują porządek w strukturze kodu poprzez dedykowane przypadki użycia.

## 2. Lokalizacja Serwisów Aplikacji w Strukturze Projektu
Serwisy aplikacji są umieszczane w katalogu zgodnym ze strukturą modułów:

```
YourProject/{moduł}/Service/
```

Każdy moduł może mieć własne serwisy aplikacji, które obsługują specyficzne przypadki użycia w ramach tego modułu.

## 3. Dziedziczenie po `Wise\Core\Service\AbstractService`
Nowe serwisy aplikacji powinny dziedziczyć po `Wise\Core\Service\AbstractService`. 

Jest to wymagane, ponieważ w wersji **2.3** wprowadziliśmy obsługę parametrów i wyniku za pomocą mechanizmu providerów. Jest to systematycznie wdrażane do nowych i starszych serwisów w celu ujednolicenia struktury.

## 4. Struktura Parametrów i Wyników

### Parametry wejściowe:
Serwisy aplikacji jako parametr przyjmują `Wise\Core\Dto\CommonServiceDTO`.

- Możesz stworzyć własną klasę parametrów, ale musi ona dziedziczyć po `CommonServiceDTO`.
- Zapewnia to jednolitą strukturę danych wejściowych, co upraszcza integrację oraz walidację.

### Zwracany wynik:
Serwis aplikacji musi zwracać jeden z poniższych wyników:
- `Wise\Core\Dto\CommonServiceDTO`, jeśli zwracany jest wynik operacji.
- `void`, jeśli metoda nie wymaga zwracania wyniku.
- Jeśli tworzysz własną strukturę wyniku, musi ona dziedziczyć po `CommonServiceDTO`, co zapewnia spójność w systemie.

## 5. Metoda `__invoke()`
Każdy serwis aplikacji korzysta z metody `__invoke()`. 

- Pozwala to na stosowanie zasady **Single Responsibility Principle** (każdy serwis zajmuje się tylko jedną logiką).
- Wymusza jednolity sposób wywołania serwisów aplikacji.

## 6. Czego nie wolno robić w Serwisach Aplikacji

❌ **Serwis aplikacji nie może być wykonywany w kontrolerze**
- Serwisy powinny być wstrzykiwane do innych komponentów, np. handlerów lub innych serwisów aplikacji.

❌ **Serwis aplikacji nie powinien zawierać logiki domenowej**
- Logika biznesowa powinna być umieszczona w **serwisach domenowych**, które są odpowiedzialne za specyficzne operacje na encjach.

❌ **Serwis aplikacji nie powinien zawierać operacji bazy danych**
- Operacje te powinny być realizowane przez serwisy **Add** lub **Modify**.
- Nie powinieneś bezpośrednio korzystać z repozytorium – serwisy **Add** i **Modify** dbają o walidację encji oraz emisję eventów.

---

**Podsumowanie:**
Serwisy aplikacji to kluczowy element architektury systemu, pozwalający na przejrzysty podział odpowiedzialności. Ich poprawne stosowanie zapewnia zgodność z SOLID, ułatwia rozwój i utrzymanie aplikacji oraz standaryzuje sposób komunikacji między warstwami aplikacji.
