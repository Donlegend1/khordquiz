<?php

namespace App\OpenApi;

use App\Models\User;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'KhordQuiz API',
    description: 'API for the KhordQuiz mobile app. Sign in to receive a Sanctum token, then send it as a Bearer token. Catalog, plans, and help are public.'
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'KhordQuiz')]
#[OA\Tag(name: 'Authentication', description: 'Account creation, sign-in, profile setup, and password reset.')]
#[OA\Schema(
    schema: 'User',
    required: ['id', 'name', 'email'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'first_name', type: 'string', nullable: true, example: 'Kings'),
        new OA\Property(property: 'last_name', type: 'string', nullable: true, example: 'Don'),
        new OA\Property(property: 'name', type: 'string', example: 'Kings Don'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'kings@example.com'),
        new OA\Property(property: 'country', type: 'string', nullable: true, example: 'Nigeria'),
        new OA\Property(property: 'skill_level', type: 'string', enum: User::SKILL_LEVELS, nullable: true, example: 'Beginner'),
        new OA\Property(property: 'avatar', type: 'string', enum: User::AVATARS, nullable: true, example: 'piano'),
        new OA\Property(property: 'current_streak', type: 'integer', example: 0),
        new OA\Property(property: 'longest_streak', type: 'integer', example: 0),
        new OA\Property(property: 'daily_goal', type: 'integer', example: 20),
        new OA\Property(property: 'terms_accepted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'AuthResponse',
    required: ['message', 'token', 'token_type', 'user'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Signed in.'),
        new OA\Property(property: 'token', type: 'string', example: '1|plain-text-token'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ]
)]
#[OA\Schema(
    schema: 'MessageResponse',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Signed out.'),
    ]
)]
#[OA\Schema(
    schema: 'UserResponse',
    required: ['user'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Profile updated.'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The email field is required.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['email' => ['The email field is required.']],
        ),
    ]
)]
#[OA\Schema(
    schema: 'RegisterRequest',
    required: ['first_name', 'last_name', 'email', 'password', 'country', 'terms'],
    properties: [
        new OA\Property(property: 'first_name', type: 'string', maxLength: 100, example: 'Kings'),
        new OA\Property(property: 'last_name', type: 'string', maxLength: 100, example: 'Don'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'kings@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'password'),
        new OA\Property(property: 'country', type: 'string', description: 'A country name from the KhordQuiz country list.', example: 'Nigeria'),
        new OA\Property(property: 'terms', type: 'boolean', description: 'Must be accepted.', example: true),
        new OA\Property(property: 'device_name', type: 'string', maxLength: 255, nullable: true, example: 'Pixel 8'),
    ]
)]
#[OA\Schema(
    schema: 'LoginRequest',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'kings@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
        new OA\Property(property: 'device_name', type: 'string', maxLength: 255, nullable: true, example: 'Pixel 8'),
    ]
)]
#[OA\Schema(
    schema: 'ForgotPasswordRequest',
    required: ['email'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'kings@example.com'),
    ]
)]
#[OA\Schema(
    schema: 'VerifyOtpRequest',
    required: ['email', 'code'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'kings@example.com'),
        new OA\Property(property: 'code', type: 'string', description: 'Six-digit code from the reset email.', pattern: '^\d{6}$', example: '123456'),
    ]
)]
#[OA\Schema(
    schema: 'VerifyOtpResponse',
    required: ['message', 'reset_token'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Code verified.'),
        new OA\Property(property: 'reset_token', type: 'string', example: 'reset-token'),
    ]
)]
#[OA\Schema(
    schema: 'ResetPasswordRequest',
    required: ['email', 'reset_token', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'kings@example.com'),
        new OA\Property(property: 'reset_token', type: 'string', description: 'Token returned by verify-otp.'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'new-password'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'new-password'),
    ]
)]
#[OA\Schema(
    schema: 'UpdateProfileRequest',
    properties: [
        new OA\Property(property: 'first_name', type: 'string', maxLength: 100, example: 'Kings'),
        new OA\Property(property: 'last_name', type: 'string', maxLength: 100, example: 'Don'),
        new OA\Property(property: 'country', type: 'string', nullable: true, example: 'Ghana'),
        new OA\Property(property: 'skill_level', type: 'string', enum: User::SKILL_LEVELS, nullable: true, example: 'Beginner'),
        new OA\Property(property: 'avatar', type: 'string', enum: User::AVATARS, nullable: true, example: 'piano'),
    ]
)]
#[OA\Post(
    path: '/api/v1/register',
    operationId: 'register',
    summary: 'Create an account',
    description: 'Creates a member, records that the terms were accepted, and returns a token for profile setup.',
    tags: ['Authentication'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest')),
    responses: [
        new OA\Response(response: 201, description: 'Account created', content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')),
        new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
    ]
)]
#[OA\Post(
    path: '/api/v1/login',
    operationId: 'login',
    summary: 'Sign in',
    tags: ['Authentication'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Signed in', content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')),
        new OA\Response(response: 422, description: 'Invalid credentials or validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
    ]
)]
#[OA\Get(
    path: '/api/v1/user',
    operationId: 'currentUser',
    summary: 'Current user',
    security: [['bearerAuth' => []]],
    tags: ['Authentication'],
    responses: [
        new OA\Response(response: 200, description: 'Current user', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
        new OA\Response(response: 401, description: 'Missing or revoked token', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
    ]
)]
#[OA\Post(
    path: '/api/v1/logout',
    operationId: 'logout',
    summary: 'Sign out',
    description: 'Revokes the token used for this request. Other devices stay signed in.',
    security: [['bearerAuth' => []]],
    tags: ['Authentication'],
    responses: [
        new OA\Response(response: 200, description: 'Signed out', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        new OA\Response(response: 401, description: 'Missing or revoked token', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
    ]
)]
#[OA\Patch(
    path: '/api/v1/profile',
    operationId: 'updateProfile',
    summary: 'Update profile',
    description: 'Used after registration to save the avatar and skill level. Send only the fields that should change.',
    security: [['bearerAuth' => []]],
    tags: ['Authentication'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateProfileRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Profile updated', content: new OA\JsonContent(ref: '#/components/schemas/UserResponse')),
        new OA\Response(response: 401, description: 'Missing or revoked token', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
    ]
)]
#[OA\Post(
    path: '/api/v1/forgot-password',
    operationId: 'forgotPassword',
    summary: 'Email a reset code',
    description: 'Sends a 6-digit code when the email belongs to an account. The response is the same either way. The code expires after 15 minutes.',
    tags: ['Authentication'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ForgotPasswordRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Request accepted', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        new OA\Response(response: 422, description: 'Validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
    ]
)]
#[OA\Post(
    path: '/api/v1/verify-otp',
    operationId: 'verifyOtp',
    summary: 'Verify a reset code',
    description: 'Five wrong attempts cancel the code.',
    tags: ['Authentication'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/VerifyOtpRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Code verified', content: new OA\JsonContent(ref: '#/components/schemas/VerifyOtpResponse')),
        new OA\Response(response: 422, description: 'Invalid or expired code', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
    ]
)]
#[OA\Post(
    path: '/api/v1/reset-password',
    operationId: 'resetPassword',
    summary: 'Set a new password',
    description: 'Uses the reset token from verify-otp and signs every device out.',
    tags: ['Authentication'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ResetPasswordRequest')),
    responses: [
        new OA\Response(response: 200, description: 'Password reset', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        new OA\Response(response: 422, description: 'Invalid reset session or validation failed', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
    ]
)]
class KhordQuizApi {}
