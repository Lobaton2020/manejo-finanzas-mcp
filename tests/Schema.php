<?php

declare(strict_types=1);

namespace Tests;

use PDO;

class Schema
{
    public static function migrate(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
        CREATE TABLE users (
            id_user INTEGER PRIMARY KEY AUTOINCREMENT,
            id_rol INTEGER NOT NULL,
            id_document_type INTEGER NOT NULL,
            number_document VARCHAR(100) NOT NULL,
            complete_name VARCHAR(100) NOT NULL,
            email VARCHAR(200) NOT NULL,
            password VARCHAR(1000) NOT NULL,
            image VARCHAR(500),
            email_verify_date DATETIME,
            recovery_pass_token VARCHAR(200),
            remember_token VARCHAR(200),
            born_date DATE,
            status TINYINT(1) NOT NULL,
            update_at DATETIME NOT NULL,
            create_at DATETIME NOT NULL
        );
        CREATE TABLE rols (id_rol INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(100));
        CREATE TABLE documenttypes (id_document_type INTEGER PRIMARY KEY AUTOINCREMENT, abrev VARCHAR(10), name VARCHAR(100));
        CREATE TABLE inflowtypes (
            id_inflow_type INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user INTEGER,
            name VARCHAR(200) NOT NULL,
            status TINYINT(1) NOT NULL,
            create_at DATETIME NOT NULL
        );
        CREATE TABLE outflowtypes (
            id_outflow_type INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user INTEGER,
            name VARCHAR(200) NOT NULL,
            status TINYINT(1) NOT NULL,
            create_at DATETIME NOT NULL
        );
        CREATE TABLE categories (
            id_category INTEGER PRIMARY KEY AUTOINCREMENT,
            id_outflow_type INTEGER NOT NULL,
            id_user INTEGER,
            name VARCHAR(200) NOT NULL,
            status TINYINT(1) NOT NULL,
            create_at DATETIME NOT NULL
        );
        CREATE TABLE porcents (
            id_porcent INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user INTEGER,
            name VARCHAR(100) NOT NULL,
            status TINYINT(1) NOT NULL,
            create_at DATETIME NOT NULL
        );
        CREATE TABLE inflows (
            id_inflow INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user INTEGER NOT NULL,
            id_inflow_type INTEGER NOT NULL,
            total FLOAT NOT NULL,
            description TEXT,
            set_date DATE NOT NULL,
            status TINYINT(1) NOT NULL,
            update_at DATETIME NOT NULL,
            create_at DATETIME NOT NULL
        );
        CREATE TABLE inflow_porcent (
            id_inflow_porcent INTEGER PRIMARY KEY AUTOINCREMENT,
            id_inflow INTEGER NOT NULL,
            id_porcent INTEGER NOT NULL,
            porcent INTEGER,
            status TINYINT(1) NOT NULL,
            create_at DATETIME NOT NULL
        );
        CREATE TABLE outflows (
            id_outflow INTEGER PRIMARY KEY AUTOINCREMENT,
            id_outflow_type INTEGER NOT NULL,
            id_user INTEGER NOT NULL,
            id_category INTEGER,
            id_porcent INTEGER NOT NULL,
            amount FLOAT NOT NULL,
            description TEXT,
            set_date DATE NOT NULL,
            status TINYINT(1) NOT NULL,
            update_at DATETIME NOT NULL,
            create_at DATETIME NOT NULL,
            is_in_budget TINYINT(1) NOT NULL DEFAULT 0
        );
        CREATE TABLE group_investments (
            id_group_investment INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            created_at DATETIME,
            updated_at DATETIME
        );
        CREATE TABLE investments (
            id_investment INTEGER PRIMARY KEY AUTOINCREMENT,
            id_outflow INTEGER NOT NULL,
            percent_annual_effective FLOAT NOT NULL DEFAULT 0,
            state VARCHAR(255) NOT NULL,
            init_date DATE NOT NULL,
            end_date DATE NOT NULL,
            real_retribution FLOAT NOT NULL DEFAULT 0,
            risk_level VARCHAR(255),
            updated_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            id_group_investment INTEGER
        );
        CREATE TABLE retirement_investments (
            id_retirement_investment INTEGER PRIMARY KEY AUTOINCREMENT,
            id_investment INTEGER NOT NULL,
            id_user INTEGER NOT NULL,
            descripcion TEXT,
            retirement_amount FLOAT NOT NULL DEFAULT 0,
            init_date DATE NOT NULL,
            end_date DATE NOT NULL,
            real_retribution FLOAT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL
        );
        CREATE TABLE investments_view (
            id_investment INTEGER NOT NULL,
            id_outflow INTEGER NOT NULL,
            percent_annual_effective FLOAT NOT NULL DEFAULT 0,
            state VARCHAR(255) NOT NULL,
            init_date DATE NOT NULL,
            end_date DATE NOT NULL,
            real_retribution FLOAT NOT NULL DEFAULT 0,
            risk_level VARCHAR(255),
            updated_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            id_group_investment INTEGER,
            id_user INTEGER NOT NULL,
            original_amount FLOAT NOT NULL DEFAULT 0,
            amount DOUBLE NOT NULL DEFAULT 0,
            description TEXT,
            name VARCHAR(200) NOT NULL DEFAULT '',
            group_investment_name VARCHAR(255),
            retirement_real_retribution DOUBLE NOT NULL DEFAULT 0,
            retirements_amount DOUBLE NOT NULL DEFAULT 0,
            earn_amount DOUBLE,
            earn_amount_all DOUBLE
        );
        CREATE TABLE budget (
            id_budget INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user INTEGER NOT NULL,
            total FLOAT NOT NULL,
            description TEXT,
            created_at DATETIME NOT NULL
        );
        CREATE TABLE view_budget (
            id_budget INTEGER NOT NULL,
            id_user INTEGER NOT NULL,
            budget FLOAT NOT NULL,
            total FLOAT,
            remain DOUBLE NOT NULL DEFAULT 0,
            percent DOUBLE NOT NULL DEFAULT 0,
            date DATE
        );
        CREATE TABLE temporal_budgets (
            id_temporal_budget INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user INTEGER NOT NULL,
            name VARCHAR(255),
            description TEXT,
            created_at DATETIME NOT NULL
        );
        CREATE TABLE temporal_budgets_outflow (
            id_temporal_budget_outflow INTEGER PRIMARY KEY AUTOINCREMENT,
            id_temporal_budget INTEGER NOT NULL,
            id_outflow_type INTEGER NOT NULL,
            id_user INTEGER NOT NULL,
            id_category INTEGER,
            id_porcent INTEGER NOT NULL,
            amount FLOAT NOT NULL,
            description TEXT,
            status TINYINT(1) NOT NULL,
            is_in_budget TINYINT(1) NOT NULL DEFAULT 0,
            update_at DATETIME NOT NULL,
            create_at DATETIME NOT NULL
        );
        CREATE TABLE view_temporal_budgets (
            id_temporal_budget INTEGER NOT NULL,
            id_user INTEGER NOT NULL,
            name VARCHAR(255),
            description TEXT,
            created_at DATETIME NOT NULL,
            total_amount DOUBLE NOT NULL DEFAULT 0
        );
        CREATE TABLE notes (
            id_note INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user INTEGER NOT NULL,
            description TEXT,
            total FLOAT NOT NULL,
            status TINYINT(1),
            create_at DATETIME NOT NULL
        );
        CREATE TABLE notificationtypes (
            key_notification_type VARCHAR(100) PRIMARY KEY,
            name VARCHAR(200) NOT NULL
        );
        CREATE TABLE notifications (
            id_notification INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user INTEGER NOT NULL,
            key_notification_type VARCHAR(100) NOT NULL,
            readed TINYINT(1) NOT NULL,
            create_at DATETIME NOT NULL
        );
        CREATE TABLE moneyloans (
            id_money_loan INTEGER PRIMARY KEY AUTOINCREMENT,
            id_user INTEGER NOT NULL,
            description TEXT,
            total FLOAT NOT NULL,
            set_date DATE NOT NULL,
            status TINYINT(1),
            create_at DATETIME NOT NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'FROM_ME'
        );
        SQL);
    }
}