/// <reference types="cypress" />

describe('Edit user successfully', () => {
    it('checks that an admin can edit users', () => {
        cy.login();
        cy.visit('/bolt/users');

        cy.get('table:nth-child(1) > tbody > tr:nth-child(6) > td:nth-child(7)').click();
        cy.wait(100);
        cy.get('table:nth-child(1) > tbody > tr:nth-child(6) > td:nth-child(7) > div > div > a:nth-child(1)').click();
        cy.wait(1000);

        cy.visit('/bolt/user-edit/4');

        cy.get('input[name="user[displayName]"]').clear();
        cy.get('input[name="user[displayName]"]').type('Tom Doe CHANGED');
        cy.get('input[name="user[email]"]').clear();
        cy.get('input[name="user[email]"]').type('tom_admin_changed@example.org');
        cy.get('#editcontent > button').scrollIntoView();
        cy.wait(1000);
        cy.get('#editcontent > button').click();

        cy.url().should('contain', 'bolt/users');
        cy.get('table:nth-child(1) > tbody > tr:nth-child(6)').children('td').eq(1).should('contain', 'Tom Doe CHANGED');
    })
});

describe('Edit user with incorrect display name, password and email', () => {
    it('checks that an admin can\'t edit a user with incorrect details', () => {
        cy.login();
        cy.visit('/bolt/user-edit/2');

        cy.get('input[name="user[displayName]"]').clear();
        cy.get('input[name="user[displayName]"]').type('x');
        cy.get('input[name="user[plainPassword]"]').type('short');
        cy.get('input[name="user[email]"]').clear();
        cy.get('input[name="user[email]"]').type('smth@nth');

        cy.get('#editcontent > button').scrollIntoView();
        cy.get('#editcontent > button').click();

        cy.url().should('contain', '/bolt/user-edit/2');
        cy.get('.field-error').eq(0).children('.help-block').children('.list-unstyled').children('li').should('contain', 'Invalid display name');
        cy.get('.field-error').eq(1).children('.help-block').children('.list-unstyled').children('li').should('contain', 'Invalid password. The password should contain at least 6 characters.');
        cy.get('.field-error').eq(2).children('.help-block').children('.list-unstyled').children('li').should('contain', 'Invalid email');
        cy.get('.form-group').eq(2).children('div').eq(1).should('contain', 'Suggested secure password');
    })
});

describe('Edit my user with incorrect display name', () => {
    it('checks that a user can\'t edit their profile with an incorrect display name', () => {
        cy.visit('/bolt/login');
        cy.get('input[name="login[username]"]').type('jane_chief');
        cy.get('input[name="login[password]"]').type('jane%1' + '{enter}');

        cy.get('div[class="toolbar-item btn-group toolbar-item__profile"]').trigger('mouseover')
        cy.get('.profile__dropdown.dropdown-menu.dropdown-menu-right').children('ul').children('li').eq(0).should('exist');

        cy.get('a[href="/bolt/profile-edit"]').click({force: true});
        cy.url().should('contain', '/bolt/profile-edit');

        cy.wait(500);

        cy.get('h1').should('contain', 'Jane Doe');
        cy.get('#user_username').invoke('val').should('contain', 'jane_chief');

        cy.get('#user_displayName').clear();
        cy.get('#user_displayName').type('a');
        cy.get('#editcontent > button').scrollIntoView();
        cy.get('#editcontent > button').click();

        cy.get('.field-error').eq(0).children('.help-block').children('.list-unstyled').children('li').should('contain', 'Invalid display name');
        cy.visit('/bolt/logout')
    })
});

describe('Edit my user to change display name', () => {
    it('checks that a user can change their display name', () => {
        cy.visit('/bolt/login');
        cy.get('input[name="login[username]"]').type('jane_chief');
        cy.get('input[name="login[password]"]').type('jane%1' + '{enter}');
        cy.visit('/bolt/profile-edit')

        cy.get('#user_displayName').clear();
        cy.get('#user_displayName').type('Administrator');
        cy.get('#editcontent > button').scrollIntoView();
        cy.get('#editcontent > button').click();

        cy.wait(500);

        cy.get('div[class="toast fade show"]').children('.toast-body').should('contain', 'User Profile has been updated!')
        cy.get('#user_displayName').invoke('val').should('contain', 'Administrator');
        cy.visit('/bolt/logout');
    })
});
