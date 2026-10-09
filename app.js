(function () {
    'use strict';

    /*
     * Shared frontend behavior.
     * The script is loaded with defer from header.php, so the DOM is ready.
     */

    function getCsrfToken() {
        var tokenInput = document.querySelector('input[name="csrf_token"]');
        if (tokenInput) {
            return tokenInput.value;
        }

        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        return tokenMeta ? tokenMeta.content : '';
    }

    function setupRegistrationValidation() {
        var form = document.getElementById('register-form');
        if (!form) {
            return;
        }

        var passwordInput = document.getElementById('password');
        var confirmationInput = document.getElementById('confirm_password');
        var feedback = document.getElementById('password-match');

        function validatePasswords() {
            var matches = passwordInput.value === confirmationInput.value;

            confirmationInput.setCustomValidity(
                matches ? '' : 'Passwords do not match.'
            );

            if (!confirmationInput.value) {
                feedback.textContent = '';
                feedback.className = 'field-hint';
                return;
            }

            feedback.textContent = matches
                ? 'Passwords match.'
                : 'Passwords do not match.';
            feedback.className = matches
                ? 'field-hint valid'
                : 'field-hint invalid';
        }

        passwordInput.addEventListener('input', validatePasswords);
        confirmationInput.addEventListener('input', validatePasswords);
        form.addEventListener('submit', function (event) {
            validatePasswords();

            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
            }
        });
    }

    function setupIngredientBuilder() {
        var list = document.getElementById('ingredients');
        var addButton = document.getElementById('add-ingredient');
        var form = document.getElementById('recipe-form');

        if (!list || !addButton) {
            return;
        }

        addButton.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'ingredient-row';
            row.innerHTML =
                '<input class="form-control" name="ingredients[]" required maxlength="255" placeholder="Another ingredient">' +
                '<button class="btn btn-danger remove-ingredient" type="button">Remove</button>';
            list.appendChild(row);
            row.querySelector('input').focus();
        });

        list.addEventListener('click', function (event) {
            var removeButton = event.target.closest('.remove-ingredient');

            if (removeButton && list.children.length > 1) {
                removeButton.closest('.ingredient-row').remove();
            }
        });

        if (form) {
            form.addEventListener('submit', function (event) {
                var inputs = list.querySelectorAll('input[name="ingredients[]"]');
                var hasIngredient = Array.prototype.some.call(
                    inputs,
                    function (input) {
                        return input.value.trim() !== '';
                    }
                );

                if (!hasIngredient) {
                    event.preventDefault();
                    alert('Please add at least one ingredient.');
                }
            });
        }
    }

    function toggleFavorite(button) {
        button.disabled = true;

        return fetch('api/toggle-favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            },
            body: JSON.stringify({
                recipe_id: button.dataset.recipeId
            })
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Favorite update failed.');
                    }
                    return data;
                });
            })
            .then(function (data) {
                var isSaved = data.status === 'added';
                button.textContent = isSaved ? '★ Saved' : '☆ Save';
                button.setAttribute('aria-label', isSaved ? 'Remove from favorites' : 'Add to favorites');
                button.setAttribute('aria-pressed', isSaved ? 'true' : 'false');
                button.classList.toggle('is-saved', isSaved);

                if (!isSaved && button.dataset.removeOnUnsave === 'true') {
                    var card = button.closest('.card');
                    if (card) {
                        card.remove();
                    }
                }
            })
            .catch(function () {
                alert('Unable to update your saved recipes. Please try again.');
            })
            .finally(function () {
                button.disabled = false;
            });
    }

    function setupFavoriteButtons() {
        document.querySelectorAll('.favorite-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                toggleFavorite(button);
            });
        });
    }

    function setupDeleteConfirmations() {
        document.querySelectorAll('[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.dataset.confirm)) {
                    event.preventDefault();
                }
            });
        });
    }

    setupRegistrationValidation();
    setupIngredientBuilder();
    setupFavoriteButtons();
    setupDeleteConfirmations();
}());
