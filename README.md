# Mini Banking API

API RESTful per la gestione di conti e movimenti con conversioni in tempo reale (Binance & Frankfurter).

## 🛠️ Schema Database
Eseguire queste query nel database `bank` prima di testare:

```sql
CREATE TABLE `accounts` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `owner_name` VARCHAR(255) NOT NULL,
  `currency` CHAR(3) DEFAULT 'EUR'
);

CREATE TABLE `transactions` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `account_id` INT NOT NULL,
  `description` VARCHAR(255),
  `amount` DECIMAL(10, 2) NOT NULL,
  `type` ENUM('deposit', 'withdrawal') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE CASCADE
);

```

## 🚦 Comandi CURL per il Test
1. Gestione Movimenti
Ottiene la lista completa di tutti i movimenti dell'account 1
curl http://localhost:8080/accounts/1/transactions
Ottiene i dettagli specifici di un singolo movimento (es. ID 5)
curl http://localhost:8080/accounts/1/transactions/5
Registra un nuovo deposito di 1000€ sull'account 1
curl -X POST http://localhost:8080/accounts/1/deposits -d '{"amount": 1000.0, "description": "Stipendio"}' -H "Content-Type: application/json"
Registra un prelievo di 50€ dall'account 1
curl -X POST http://localhost:8080/accounts/1/withdrawals -d '{"amount": 50.0, "description": "Spesa"}' -H "Content-Type: application/json"
Modifica solo la descrizione del movimento con ID 5
curl -X PUT http://localhost:8080/accounts/1/transactions/5 -d '{"description": "Nuova descrizione"}' -H "Content-Type: application/json"
Elimina definitivamente il movimento con ID 5
curl -X DELETE http://localhost:8080/accounts/1/transactions/5
2. Saldo e Conversioni
Calcola e restituisce il saldo attuale dell'account 1 (Depositi - Prelievi)
curl http://localhost:8080/accounts/1/balance
Converte il saldo attuale in Dollari (USD) usando l'API Frankfurter
curl "http://localhost:8080/accounts/1/balance/convert/fiat?to=USD"
Converte il saldo attuale in Bitcoin (BTC) usando l'API di Binance
curl "http://localhost:8080/accounts/1/balance/convert/crypto?to=BTC"