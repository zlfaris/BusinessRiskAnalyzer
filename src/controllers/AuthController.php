<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Helpers\Validator;
use Exception;
use InvalidArgumentException;

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function register(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        try {
            if (empty($name)) {
                throw new InvalidArgumentException("Name is required.");
            }

            Validator::validateAuth($email, $password);

            $existingUser = $this->userModel->findByEmail($email);
            if ($existingUser) {
                http_response_code(409); // Conflict
                echo json_encode(['error' => 'Email is already registered.']);
                return;
            }

            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            $userId = $this->userModel->create($name, $email, $passwordHash);
            
            http_response_code(201);
            echo json_encode([
                'message' => 'Registration successful.',
                'user_id' => $userId
            ]);

        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'An internal error occurred.']);
        }
    }

    public function login(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required.']);
            return;
        }

        $user = $this->userModel->findByEmail($email);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];

            http_response_code(200);
            echo json_encode([
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid email or password.']);
        }
    }

    public function me(): void
    {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated.']);
            return;
        }

        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found.']);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ]);
    }

    public function logout(): void
    {
        // Clear all session variables
        $_SESSION = [];

        // If it's desired to kill the session, also delete the session cookie.
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Finally, destroy the session.
        session_destroy();
        
        http_response_code(200);
        echo json_encode(['message' => 'Logged out successfully.']);
    }
}
