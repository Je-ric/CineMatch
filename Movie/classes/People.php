<?php
require_once __DIR__ . '/../db/config.php';

class People
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->conn;
    }

    // Add person if not exists; return person_id
    public function addPerson(string $name): int
    {
        $name = trim($name);
        if ($name === '') return 0;

        // Check if person already exists
        $stmt = $this->db->prepare("SELECT id FROM movie_people WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $stmt->close();
            return (int)$row['id'];
        }
        $stmt->close();

        // Insert new person
        $stmt = $this->db->prepare("INSERT INTO movie_people (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return (int)$id;
    }

    // Attach person to movie (Director or Cast)
    public function attachToMovie(int $movieId, int $personId, string $role): bool
    {
        if (!in_array($role, ['Director', 'Cast'], true)) return false;

        // Prevent duplicates
        $stmt = $this->db->prepare(
            "SELECT id FROM movie_cast WHERE movie_id = ? AND person_id = ? AND role = ?"
        );
        $stmt->bind_param("iis", $movieId, $personId, $role);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->fetch_assoc()) {
            $stmt->close();
            return false; // already linked
        }
        $stmt->close();

        // Insert into movie_cast
        $stmt = $this->db->prepare(
            "INSERT INTO movie_cast (movie_id, person_id, role) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iis", $movieId, $personId, $role);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // Get all people linked to a movie (optional by role)
    public function getMoviePeople(int $movieId, string $role = ''): array
    {
        if ($role !== '') { // when role is given, mainly used
            $stmt = $this->db->prepare(
                "SELECT movie_cast.id, movie_people.name 
                    FROM movie_cast
                    JOIN movie_people ON movie_cast.person_id = movie_people.id
                    WHERE movie_cast.movie_id = ? AND movie_cast.role = ?
                    ORDER BY movie_people.name"
            );
            $stmt->bind_param("is", $movieId, $role);
        } else { // return all without filter, parang fallback lang incase
            $stmt = $this->db->prepare(
                "SELECT movie_cast.id, movie_people.name 
                    FROM movie_cast
                    JOIN movie_people ON movie_cast.person_id = movie_people.id
                    WHERE movie_cast.movie_id = ?
                    ORDER BY movie_people.name"
            );
            $stmt->bind_param("i", $movieId);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    public function removeFromMovie(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM movie_cast WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function search(string $term): array
    {
        $term = trim($term);
        if ($term === '') return [];

        $db = $this->db;

        $sql = "SELECT id, name
                FROM movie_people
                WHERE name LIKE CONCAT('%', ?, '%')
                ORDER BY name ASC
                LIMIT 15";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $term);
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = ['id' => (int)$row['id'], 'name' => $row['name']];
        }
        $stmt->close();
        return $rows;
    }
}
