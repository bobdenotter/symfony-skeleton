/// <reference types="cypress" />

describe('View users and permissions', () => {
    it('checks that an admin can view users and permission', () => {
        cy.login();
        cy.get('a[href="/bolt/menu/configuration"]').click();

        cy.wait(1000);
        cy.get('.menupage').find('ul').find('li').eq(0).click();
        cy.wait(1000);
        cy.url().should('contain', '/bolt/users');

        // users table
        cy.get('table').eq(0).find('thead').find('tr').find('th').eq(0).should('contain', '#');
        cy.get('table').eq(0).find('thead').find('tr').find('th').eq(1).should('contain', 'Username');
        cy.get('table').eq(0).find('thead').find('tr').find('th').eq(2).should('contain', 'Display name / Email');
        cy.get('table').eq(0).find('thead').find('tr').find('th').eq(3).should('contain', 'Roles');
        cy.get('table').eq(0).find('thead').find('tr').find('th').eq(4).should('contain', 'Session age');
        cy.get('table').eq(0).find('thead').find('tr').find('th').eq(5).should('contain', 'Last IP');
        cy.get('table').eq(0).find('thead').find('tr').find('th').eq(6).should('contain', 'Actions');

        cy.get('table').eq(0).find('tbody').find('tr').its('length').should('eq', 6);

        cy.get('table').eq(0).find('tbody').find('tr').find('th').should('contain', '1');
        cy.get('table').eq(0).find('tbody').find('tr').find('td').eq(0).should('contain', 'admin');
        cy.get('table').eq(0).find('tbody').find('tr').find('td').eq(1).should('contain', 'Admin /');
        cy.get('table').eq(0).find('tbody').find('tr').find('td').eq(2).should('contain', 'ROLE_DEVELOPER');
        cy.get('table').eq(0).find('tbody').find('tr').find('td').eq(4).find('small').find('code').should('contain', '127.0.0.1');
        cy.get('table').eq(0).find('tbody').find('tr').find('td').eq(5).should('contain', 'Options');
    })
});

describe('View current sessions', () => {
    it('checks that an admin can view the currently running sessions', () => {
        cy.login();
        cy.visit('/bolt/users');

        cy.get('h3').should('contain', 'Current sessions');

        // sessions table
        cy.get('table').eq(1).find('thead').find('tr').find('th').eq(0).should('contain', '#');
        cy.get('table').eq(1).find('thead').find('tr').find('th').eq(1).should('contain', 'Username');
        cy.get('table').eq(1).find('thead').find('tr').find('th').eq(2).should('contain', 'Session age');
        cy.get('table').eq(1).find('thead').find('tr').find('th').eq(3).should('contain', 'Session expires');
        cy.get('table').eq(1).find('thead').find('tr').find('th').eq(4).should('contain', 'IP address');
        cy.get('table').eq(1).find('thead').find('tr').find('th').eq(5).should('contain', 'Browser / platform');

        cy.get('table').eq(1).find('tbody').find('tr').its('length').should('eq', 1);
        
        cy.get('table').eq(1).find('tbody').find('tr').find('td').eq(0).should('contain', '1');
        cy.get('table').eq(1).find('tbody').find('tr').find('td').eq(1).should('contain', 'admin');
        cy.get('table').eq(1).find('tbody').find('tr').find('td').eq(3).should('contain', 'in 13 days');
        cy.get('table').eq(1).find('tbody').find('tr').find('td').eq(4).find('code').should('contain', '127.0.0.1');
    })
});
