<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240615141933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Créer une table temporaire avec les nouvelles colonnes
        $this->addSql('CREATE TABLE __temp__event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, titre VARCHAR(255) NOT NULL, description CLOB NOT NULL, date_heure DATETIME NOT NULL, max_participants INTEGER NOT NULL, publique BOOLEAN NOT NULL, is_paid BOOLEAN DEFAULT 0 NOT NULL, cost NUMERIC(10, 2) DEFAULT NULL, CONSTRAINT FK_3BAE0AA7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        
        // Copier les données de l'ancienne table vers la nouvelle table
        $this->addSql('INSERT INTO __temp__event (id, user_id, titre, description, date_heure, max_participants, publique) SELECT id, user_id, titre, description, date_heure, max_participants, publique FROM event');
        
        // Supprimer l'ancienne table
        $this->addSql('DROP TABLE event');
        
        // Renommer la table temporaire en event
        $this->addSql('ALTER TABLE __temp__event RENAME TO event');
        
        // Créer les index
        $this->addSql('CREATE INDEX IDX_3BAE0AA7A76ED395 ON event (user_id)');
    }

    public function down(Schema $schema): void
    {
        // Créer une table temporaire sans les nouvelles colonnes
        $this->addSql('CREATE TABLE __temp__event (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, titre VARCHAR(255) NOT NULL, description CLOB NOT NULL, date_heure DATETIME NOT NULL, max_participants INTEGER NOT NULL, publique BOOLEAN NOT NULL, CONSTRAINT FK_3BAE0AA7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        
        // Copier les données de la nouvelle table vers l'ancienne table
        $this->addSql('INSERT INTO __temp__event (id, user_id, titre, description, date_heure, max_participants, publique) SELECT id, user_id, titre, description, date_heure, max_participants, publique FROM event');
        
        // Supprimer la nouvelle table
        $this->addSql('DROP TABLE event');
        
        // Renommer la table temporaire en event
        $this->addSql('ALTER TABLE __temp__event RENAME TO event');
        
        // Créer les index
        $this->addSql('CREATE INDEX IDX_3BAE0AA7A76ED395 ON event (user_id)');
    }
}
