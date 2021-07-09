/// <reference types="cypress" />

describe('Create/delete user', () => {
    it('checks that an admin can create and delete users', () => {
        cy.login();
        cy.visit('/bolt/users');
        cy.get('main > p > a').eq(0).scrollIntoView();
        cy.get('main > p > a').eq(0).click();

        cy.url().should('contain', '/bolt/user-edit/add');
        cy.get('h1').should('contain', 'New User');
        cy.wait(100);

        cy.get('input[name="user[username]"]').type('test_user');
        cy.get('input[name="user[displayName]"]').type('Test user');
        cy.get('input[name="user[plainPassword]"]').type('test%1');
        cy.get('input[name="user[email]"]').type('test_user@example.org');

        cy.get('#multiselect-user_locale > div > div.multiselect__select').scrollIntoView();
        cy.get('#multiselect-user_locale > div > div.multiselect__select').click();
        cy.get('#multiselect-user_locale > div > div.multiselect__content-wrapper > ul > li:nth-child(1)').scrollIntoView();
        cy.get('#multiselect-user_locale > div > div.multiselect__content-wrapper > ul > li:nth-child(1)').click();
        cy.get('#multiselect-user_roles > div > div.multiselect__select').scrollIntoView();
        cy.get('#multiselect-user_roles > div > div.multiselect__select').click();
        cy.get('#multiselect-user_roles > div > div.multiselect__content-wrapper > ul > li:nth-child(1) > span').scrollIntoView();
        cy.get('#multiselect-user_roles > div > div.multiselect__content-wrapper > ul > li:nth-child(1) > span').click();

        cy.get('#editcontent > button').scrollIntoView();
        cy.get('form[id="editcontent"]').submit();

        cy.visit('/bolt/users');
        cy.get('table').eq(0).find('tbody').find('tr').its('length').should('eq', 7);
        cy.get('table').eq(0).find('tbody').find('tr').eq(5).find('td').eq(0).should('contain', 'test_user');
        cy.get('table').eq(0).find('tbody').find('tr').eq(5).find('td').eq(1).find('small').find('a').should('contain', '@');
        cy.get('table').eq(0).find('tbody').find('tr').eq(5).find('td').eq(1).should('contain', 'Test user');

        cy.get('table').eq(0).find('tbody').find('tr').eq(5).find('td').eq(5).click();
        cy.wait(100); 
        cy.get('table').eq(0).find('tbody').find('tr').eq(5).find('td').eq(5).find('.btn-hidden-danger').click();
        cy.wait(1000);
        cy.get('.bootbox-body').should('contain', 'Are you sure you wish to delete this content?');
        cy.get('.modal-footer').find('button').eq(1).should('contain', 'OK').click();

        cy.visit('/bolt/users');
        cy.wait(1000);
        cy.get('table').eq(0).find('tbody').find('tr').its('length').should('eq', 6);
        cy.get('table').eq(0).find('tbody').find('tr').eq(5).find('td').eq(0).should('not.contain', 'test_user');
        cy.get('table').eq(0).find('tbody').find('tr').eq(5).find('td').eq(1).should('not.contain', 'Test user');
    })
});
