CREATE TABLE `accounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `owner_name` VARCHAR(255) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'EUR',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `account_id` INT(11) NOT NULL,
  `description` VARCHAR(255) NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `type` ENUM('deposit', 'withdrawal') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `balance_after` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) ON DELETE CASCADE,
  CHECK (amount > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `accounts` (`owner_name`, `currency`) VALUES
('Mario Rossi', 'EUR');

INSERT INTO `transactions` (`account_id`, `type`, `amount`, `description`, `balance_after`) VALUES
(1, 'deposit', 1000.00, 'Deposito iniziale', 1000.00),
(1, 'withdrawal', 200.00, 'Spesa supermercato', 800.00),
(1, 'deposit', 500.00, 'Stipendio', 1300.00),
(1, 'withdrawal', 150.00, 'Bollette', 1150.00);
