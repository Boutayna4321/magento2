<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Model\FormDefinition;
use AlpineCommerce\Turnstile\Model\FormRegistry;
use Magento\Framework\Module\Manager as ModuleManager;
use PHPUnit\Framework\TestCase;

class FormRegistryTest extends TestCase
{
    /**
     * @param array<string, mixed> $forms
     * @param string[] $enabledModules
     */
    private function registry(array $forms, array $enabledModules = ['Magento_Contact', 'Magento_Customer']): FormRegistry
    {
        $moduleManager = $this->createMock(ModuleManager::class);
        $moduleManager->method('isEnabled')->willReturnCallback(
            static fn (string $module): bool => in_array($module, $enabledModules, true)
        );

        return new FormRegistry($moduleManager, $forms);
    }

    private function contact(): FormDefinition
    {
        return new FormDefinition('contact', 'Contact Us', ['contact_index_post'], 'Magento_Contact', 'auto', null, [], null, null, 10);
    }

    private function login(): FormDefinition
    {
        return new FormDefinition('customer_login', 'Customer Login', ['customer_account_loginpost'], 'Magento_Customer', 'auto', null, [], null, null, 30);
    }

    public function testFindByActionReturnsTheRegisteredForm(): void
    {
        $contact = $this->contact();

        $this->assertSame($contact, $this->registry(['contact' => $contact])->findByAction('contact_index_post'));
    }

    public function testFindByActionIsCaseInsensitive(): void
    {
        $contact = $this->contact();

        $this->assertSame($contact, $this->registry(['contact' => $contact])->findByAction('Contact_INDEX_Post'));
    }

    public function testFindByActionReturnsNullForUnprotectedRoute(): void
    {
        $this->assertNull($this->registry(['contact' => $this->contact()])->findByAction('catalogsearch_result_index'));
    }

    public function testFormOfDisabledModuleIsHiddenAndNeverApplied(): void
    {
        $registry = $this->registry(['contact' => $this->contact(), 'customer_login' => $this->login()], ['Magento_Customer']);

        $this->assertSame(['customer_login'], array_keys($registry->getAll()));
        $this->assertNull($registry->get('contact'));
        $this->assertNull($registry->findByAction('contact_index_post'));
    }

    public function testFormWithoutRequiredModuleIsAlwaysAvailable(): void
    {
        $form = new FormDefinition('blog_comment', 'Blog Comment', ['blog_comment_post']);
        $registry = $this->registry(['blog_comment' => $form], []);

        $this->assertSame($form, $registry->get('blog_comment'));
        $this->assertSame($form, $registry->findByAction('blog_comment_post'));
    }

    public function testGetAllIsSortedBySortOrderThenId(): void
    {
        $zeta = new FormDefinition('zeta', 'Zeta', ['zeta_index_post'], null, 'auto', null, [], null, null, 10);
        $registry = $this->registry(['customer_login' => $this->login(), 'zeta' => $zeta, 'contact' => $this->contact()]);

        $this->assertSame(['contact', 'zeta', 'customer_login'], array_keys($registry->getAll()));
    }

    public function testEmptyRegistryProtectsNothing(): void
    {
        $registry = $this->registry([]);

        $this->assertSame([], $registry->getAll());
        $this->assertNull($registry->findByAction('contact_index_post'));
    }

    /**
     * @dataProvider invalidIds
     */
    public function testRejectsInvalidFormId(string $id): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Turnstile form id');

        $this->registry([$id => new FormDefinition($id, 'Form', ['route_controller_action'])])->getAll();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidIds(): array
    {
        return [
            'uppercase' => ['Contact'],
            'hyphen' => ['contact-us'],
            'path' => ['../contact'],
            'markup' => ['<b>x</b>'],
            'empty' => [''],
            'too long' => [str_repeat('a', 33)],
        ];
    }

    public function testRejectsKeyThatDiffersFromFormId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is registered under the key "contact_us"');

        $this->registry(['contact_us' => $this->contact()])->getAll();
    }

    public function testRejectsItemThatIsNotAFormDefinition(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        $this->registry(['contact' => new \stdClass()])->getAll();
    }

    public function testRejectsActionClaimedByTwoForms(): void
    {
        $other = new FormDefinition('contact_copy', 'Copy', ['CONTACT_index_post']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Action "contact_index_post" is claimed by Turnstile forms "contact" and "contact_copy"');

        $this->registry(['contact' => $this->contact(), 'contact_copy' => $other])->findByAction('contact_index_post');
    }

    public function testValidationRunsEvenForFormsOfDisabledModules(): void
    {
        $broken = new FormDefinition('Broken', 'Broken', ['broken_index_post'], 'Magento_Missing');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Turnstile form id "Broken"');

        $this->registry(['Broken' => $broken])->getAll();
    }
}
