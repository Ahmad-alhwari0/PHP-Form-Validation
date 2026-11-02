<?php

class User
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function findbyemail(string $email): ?array
    {

        $sql = "SELECT *FROM users WHERE email=?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        return $user   ?: null;
    }

    public function register(array $data): int
    {
        $hashedPassword = password_hash(
            $data['password'],
            PASSWORD_DEFAULT
        );

        $sql = "INSERT INTO users (
                first_name, middle_name, last_name, family_name,
                email, mobile, password, date_of_birth
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['family_name'],
            $data['email'],
            $data['mobile'],
            $hashedPassword,
            $data['date_of_birth']
        ]);

        return (int) $this->conn->lastInsertId();
    }

    public function login(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        $sql = "UPDATE users SET last_login_at = NOW() WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user['id']]);

        unset($user['password']);

        return $user;
    }
    public function getAll(): array
    {
        $sql = "SELECT
                id, first_name, middle_name, last_name, family_name,
                email, mobile, date_of_birth, role,
                created_at, last_login_at
            FROM users
            ORDER BY id DESC";

        $stmt = $this->conn->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT
                id, first_name, middle_name, last_name, family_name,
                email, mobile, date_of_birth, role,
                created_at, last_login_at
            FROM users
            WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE users SET
                first_name = ?,
                middle_name = ?,
                last_name = ?,
                family_name = ?,
                email = ?,
                mobile = ?,
                date_of_birth = ?
            WHERE id = ?";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['family_name'],
            $data['email'],
            $data['mobile'],
            $data['date_of_birth'],
            $id
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
}
