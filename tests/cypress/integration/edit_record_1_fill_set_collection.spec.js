/// <reference types="cypress" />

describe('As an Admin I want to fill in a Set', () => {
    it('checks if an admin can fill in a set', () => {
        cy.login();
        cy.visit('/bolt/edit/43');
        cy.get('.editor__tabbar').should('contain', 'Sets');

        cy.get('a[id="sets-tab"]').click();
        cy.url().should('contain', '/bolt/edit/43#sets');

        cy.get("#sets label[for='field-set-title']").should('contain', 'Title');
        cy.get('input[name="sets[set][title]"]').its('length').should('eq', 1);
        cy.get("#sets label[for='field-set-textarea']").should('contain', 'Textarea');
        cy.get('textarea[name="sets[set][textarea]"]').its('length').should('eq', 1);

        cy.get('input[name="sets[set][title]"]').clear();
        cy.get('input[name="sets[set][title]"]').type('Foo');
        cy.get('textarea[name="sets[set][textarea]"]').clear();
        cy.get('textarea[name="sets[set][textarea]"]').type('Bar');

        cy.get('button[class="btn btn-success mb-0 "]').eq(1).scrollIntoView();
        cy.get('button[class="btn btn-success mb-0 "]').eq(1).click();

        cy.url().should('contain', '/bolt/edit/43?edit_locale=en#sets');
        cy.get('input[name="sets[set][title]"]').should('have.value', 'Foo');
        cy.get('textarea[name="sets[set][textarea]"]').should('have.value', 'Bar');

    })
});

describe('As an Admin I want to fill in a Collection', () => {
    it('checks if an admin can fill in a collection', () => {
        cy.login();
        cy.visit('/bolt/edit/43');
        cy.get('.editor__tabbar').should('contain', 'Collections');

        cy.get('a[id="collections-tab"]').click();
        cy.url().should('contain', '/bolt/edit/43#collections');
        cy.get("label[for='field-collection']").should('contain', "Collection:");

        cy.get('button[id="collection-dropdownMenuButton"]').scrollIntoView();
        cy.get('button[id="collection-dropdownMenuButton"]').click();
        cy.get("#field-collection-collection > div > div > div> div.dropdown.show > div > a:nth-child(2)").click();

        cy.get('.action-move-up-collection-item').eq(0).should('be.disabled');
        cy.get('.action-move-down-collection-item').eq(2).should('be.disabled');

        cy.get('button[id="collection-dropdownMenuButton"]').scrollIntoView();
        cy.get('button[id="collection-dropdownMenuButton"]').click();
        cy.get("#field-collection-collection > div > div > div> div.dropdown.show > div > a:nth-child(1)").click();

        cy.get('.collection-item').its('length').should('eq', 4);
        cy.get('#collections textarea').eq(1).clear();
        cy.get('#collections textarea').eq(1).type('Bye, Bolt');
        cy.get(".collection-item input[type='text']").eq(1).clear();
        cy.get(".collection-item input[type='text']").eq(1).type('Hey, Bolt');

        cy.get('.collection-item:nth-child(4) .action-move-down-collection-item').scrollIntoView();
        cy.get('.collection-item:nth-child(4) .action-move-down-collection-item').click();
        cy.get('div[data-label="Set inside Collection"]').should('exist');

        cy.get('button[class="btn btn-success mb-0 "]').eq(1).scrollIntoView();
        cy.get('button[class="btn btn-success mb-0 "]').eq(1).click();
        cy.url().should('contain', '/bolt/edit/43?edit_locale=en#collections');

        cy.get(".collection-item:nth-child(4) input[type='text']").should('have.value', 'Hey, Bolt');
        cy.get('textarea[name="collections[collection][textarea][4]"]').should('have.value', 'Bye, Bolt');
        cy.get('.collection-item:nth-child(4) .collection-item-title').should('contain', 'Hey, Bolt');
        cy.get('.collection-item:nth-child(5) .collection-item-title').should('contain', 'Bye, Bolt');

        cy.get('#collections textarea').eq(1).clear();
        cy.get(".collection-item input[type='text']").eq(1).clear();

        cy.get('.collection-item:nth-child(4) .collection-item-title').should('contain', 'Set');
        cy.get('.collection-item:nth-child(5) .collection-item-title').should('contain', 'Textarea');

        cy.get('.collection-item:nth-child(4) .action-remove-collection-item').scrollIntoView();
        cy.get('.collection-item:nth-child(4) .action-remove-collection-item').click();
        cy.get('.modal-dialog').should('have.length', 1);
        cy.get('.modal-dialog').should('contain', 'Are you sure you wish to delete this collection item?');
        cy.get('button[class="btn btn-primary bootbox-accept"]').click();

        cy.wait(1000);

        cy.get('.collection-item:nth-child(4) .action-remove-collection-item').scrollIntoView();
        cy.get('.collection-item:nth-child(4) .action-remove-collection-item').click();
        cy.get('.modal-dialog').should('have.length', 1);
        cy.get('.modal-dialog').should('contain', 'Are you sure you wish to delete this collection item?');
        cy.get('button[class="btn btn-primary bootbox-accept"]').click();

        cy.wait(1000);

        cy.get('.collection-item').its('length').should('eq', 2);
        cy.get('button[class="btn btn-success mb-0 "]').eq(1).scrollIntoView();
        cy.get('button[class="btn btn-success mb-0 "]').eq(1).click({force: true});

        cy.get('.collection-item').its('length').should('eq', 2);
        cy.get('.collection-item-title').should('not.contain', 'Hey, Bolt');
        cy.get('.collection-item-title').should('not.contain', 'Bye, Bolt');
    })
});
