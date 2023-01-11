<?php

namespace wizarphics\wizarframework\auth\authenticators;

use stdClass;
use wizarphics\wizarframework\auth\AuthResult;
use wizarphics\wizarframework\interfaces\AuthenticationInterface;
use wizarphics\wizarframework\UserModel;

class TokenAuth implements AuthenticationInterface
{
	protected UserModel $userHandler;
	public function __construct(UserModel $userHandler)
	{
		$this->userHandler = $userHandler;
	}

	/**
	 * Attempts to authenticate a user with the given $credentials.
	 * Logs the user in with a successful check.
	 *
	 * @param array $credentials
	 * @return AuthResult
	 */
	public function attempt(array $credentials): AuthResult
	{
		/** @var \wizarphics\wizarframework\http\Request $request */
		$request = app('request');

		$ipAddress = $request->getIPAddress();
		$result = $this->check($credentials);

		if (!$result->isOK()) {

			// Todo: Emit Event to Record login attempt

			return $result;
		}

		$user = $result->info();

		$user = $user->setAccessToken(
			$user->getAccessToken($this->fetchBearerToken())
		);

		$this->login($user);

		// Todo: Emit Event to Record login attempt

		return $result;
	}

	/**
	 * Checks a user's $credentials to see if they match an
	 * existing user.
	 *
	 * @param array $credentials
	 * @return AuthResult
	 */
	public function check(array $credentials): AuthResult
	{
		if (!array_key_exists('token', $credentials) || empty($credentials['token'])) {
			return new AuthResult([
				'success' => false,
				'reason'  => lang('Auth.noToken', [config('Auth')->authenticatorHeader['tokens']]),
			]);
		}

		if (strpos($credentials['token'], 'Bearer') === 0) {
			$credentials['token'] = trim(substr($credentials['token'], 6));
		}

		/** @var UserIdentityModel $identityModel */
		$identityModel = model(UserIdentityModel::class);

		$token = $identityModel->getAccessTokenByRawToken($credentials['token']);

		if ($token === null) {
			return new AuthResult([
				'success' => false,
				'reason'  => __('Auth.badToken'),
			]);
		}

		// Hasn't been used in a long time
		if (
			$token->last_used_at
			&& $this->validateLastUsedAt($token->last_used_at)
		) {
			return new AuthResult([
				'success' => false,
				'reason'  => __('Auth.oldToken'),
			]);
		}

		$token->last_used_at = date('Y-m-d H:i:s');

		if ($token->hasChanged()) {
			$identityModel->save($token);
		}

		// Ensure the token is set as the current token
		$user = $token->user();
		$user->setAccessToken($token);

		return new AuthResult([
			'success'   => true,
			'extraInfo' => $user,
		]);
	}

	public function validateLastUsedAt(stdClass $token)
	{
		
	}

	/**
	 * Checks if the user is currently logged in.
	 * @return bool
	 */
	public function loggedIn(): bool
	{
	}

	/**
	 * Logs the given user in.
	 * On success this must trigger the "login" Event.
	 *
	 * @param UserModel $user
	 */
	public function login(UserModel $user): void
	{
	}

	/**
	 * Logs a user in based on their ID.
	 * On success this must trigger the "login" Event.
	 *
	 * @param int|string $userId
	 */
	public function loginById($userId): void
	{
	}

	/**
	 * Logs the current user out.
	 * On success this must trigger the "logout" Event.
	 */
	public function logout(): void
	{
	}

	/**
	 * Returns the currently logged in user.
	 * @return UserModel|null
	 */
	public function getUser(): ?UserModel
	{
	}

	/**
	 * Updates the user's last active date.
	 */
	public function recordActiveDate(): void
	{
	}
}
