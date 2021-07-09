/// <reference types="cypress" />

describe('Disable/enable users', () => {
    it('checks that an admin can disable/enable another user', () => {
        cy.visit('/bolt/login');
        cy.get('input[name="login[username]"]').type('jane_chief');
        cy.get('input[name="login[password]"]').type('jane%1' + '{enter}');
        cy.get('h1').find('span').should('contain', 'Bolt Dashboard');
    
        cy.visit('bolt/logout');
        cy.login();
        cy.visit('bolt/users');

        cy.get('table').eq(0).find('tbody').find('tr').eq(2).find('td').eq(5).click();
        cy.wait(100);
        cy.get('table').eq(0).find('tbody').find('tr').eq(2).find('td').eq(5).find('a').eq(1).click();
        cy.wait(1000);

        cy.get('table').eq(0).find('tbody').find('tr').eq(2).find('td').eq(5).click();
        cy.wait(100);
        cy.get('table').eq(0).find('tbody').find('tr').eq(2).find('td').eq(5).find('a').eq(1).should('contain', 'Enable');

        cy.visit('bolt/logout');
        cy.visit('/bolt/login');
        cy.get('input[name="login[username]"]').type('jane_chief');
        cy.get('input[name="login[password]"]').type('jane%1' + '{enter}');
        cy.get('div[class="alert alert-danger"]').should('contain', 'User is disabled');

        cy.visit('bolt/logout');
        cy.login();
        cy.visit('bolt/users');
        cy.get('table').eq(0).find('tbody').find('tr').eq(2).find('td').eq(5).click();
        cy.wait(100);

        cy.get('table').eq(0).find('tbody').find('tr').eq(2).find('td').eq(5).find('a').eq(1).should('contain', 'Enable');
        cy.get('table').eq(0).find('tbody').find('tr').eq(2).find('td').eq(5).find('a').eq(1).click();
        cy.wait(100);

        cy.visit('bolt/logout');
        cy.visit('/bolt/login');
        cy.get('input[name="login[username]"]').type('jane_chief');
        cy.get('input[name="login[password]"]').type('jane%1' + '{enter}');
        cy.url().should('contain', '/bolt/');
        cy.get('.admin__header--title__prefix').should('contain', 'Bolt Dashboard');
        cy.visit('bolt/logout');
    })
});
