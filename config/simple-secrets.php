<?php

/**
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */

return [
    'global_middleware' => [
        /**
         * The "name" refers to the middleware that will be applied globally.
         * The default middleware is "secrets_active" which requires all types specified to be active.
         * The alternative middleware would be "secrets_active_or" which require at least one type to be active.
         */
        'name' => 'secrets_active',

        /**
         * The "types" is a list of secret types that will be considered by the specified global middleware above.
         */
        'types' => [
            'password',
        ],

        /**
         * The "except_routes" is a list of URI's that should be reachable even when the user has no active secret.
         */
        'except_routes' => [
            //
        ],
    ],

    /**
     * Stale secrets will be purged from the database after specified amount of time.
     * When set to null, stale secrets will not be deleted.
     */
    'purge_stale_after' => '7 days',

    /**
     * The "middlewares" will be used by the "SecretsController".
     * The key represents the function name while the value will be the middleware to be applied.
     */
    'middlewares' => [
        'index' => 'auth:api',
        'show' => 'auth:api',
        'destroy' => 'auth:api',
    ],

    /**
     * Types of User Secrets
     */
    'types' => [
        'password' => [
            /**
             * The "code" will be used during saving of secrets to the database.
             * The "code" must be a value between 0-255 and must be unique.
             */
            'code' => 0,

            /**
             * The "display_name" will be used on exception messages.
             **/
            'display_name' => 'password',

            /**
             * The "accessor_name" will be used to get secrets from the User model.
             **/
            'accessor_name' => 'password',

            /**
             * The "relationship_name" will be used to bridge the User model to a specific Secret type.
             * The name must be in plural form as it will represent a has-many relationship.
             **/
            'relationship_name' => 'passwords',

            /**
             * The "max_active_count" refers to how many secrets should be active at one time.
             * When set to null, all secrets of the same type are considered as active.
             **/
            'max_active_count' => 1,

            /**
             * The "max_history_count" refers to how many secrets should be kept and used during uniqueness validation.
             * When set to null, the new secret will be compared to all previous secrets available of the same type.
             **/
            'max_history_count' => 4,

            /**
             * The "max_usage_count" refers to how many times a secret can be required during requests.
             * The "max_usage_count" must be a value between 0-255.
             * When set to null, the secret will always be usable.
             **/
            'max_usage_count' => null,

            /**
             * The "expires_after" will be used to set the expiration date of a secret.
             * When set to null, the secret will not expire.
             **/
            'expires_after' => '90 days',

            /**
             * The "broadcast_expiring_before" will decide when to start broadcasting "SecretExpiringEvent".
             * When set to null, the event will not be broadcast.
             */
            'broadcast_expiring_before' => '10 days',

            /**
             * The "hashed" will decide whether to hash the secret or not before saving to the database.
             */
            'hashed' => true,

            /**
             * The "unique_for_all" will decide whether to add a new authentication guard for this type.
             * The "hashed" must be set to false since we can't check for uniqueness with hashed values.
             */
            'unique_for_all' => false,

            /**
             * The "hidden" will decide whether the secret value is visible or not on responses.
             */
            'hidden' => true,

            /**
             * The "append" will decide whether to always append the secret to the User model.
             * Even if "append" is true, the secret value will still be invisible on responses if "hidden" is true.
             */
            'append' => false,
        ],
    ],
];
