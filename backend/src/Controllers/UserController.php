<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\User;

/**
 * User Controller
 * Handles HTTP requests for user resources
 */
class UserController
{
    /**
     * GET /api/users
     * Get all users
     */
    public function index(): void
    {
        try {
            $users = User::all();

            Response::json([
                'success' => true,
                'data' => $users,
                'count' => count($users)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/users/{id}
     * Get single user by ID
     */
    public function show(int $id): void
    {
        try {
            $user = User::find($id);

            if (!$user) {
                Response::json([
                    'success' => false,
                    'error' => 'User not found'
                ], 404);
                return;
            }

            Response::json([
                'success' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/users/active
     * Get active users only
     */
    public function active(): void
    {
        try {
            $users = User::getActive();

            Response::json([
                'success' => true,
                'data' => $users,
                'count' => count($users)
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Failed to fetch active users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/auth/login
     * Authenticate user
     */
    public function login(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['username']) || !isset($input['password'])) {
                Response::json([
                    'success' => false,
                    'error' => 'Username and password are required'
                ], 400);
                return;
            }

            $user = User::authenticate($input['username'], $input['password']);

            if (!$user) {
                Response::json([
                    'success' => false,
                    'error' => 'Invalid credentials or inactive account'
                ], 401);
                return;
            }

            // In production, generate JWT token here
            Response::json([
                'success' => true,
                'data' => $user,
                'message' => 'Login successful'
            ]);
        } catch (\Exception $e) {
            Response::json([
                'success' => false,
                'error' => 'Login failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
