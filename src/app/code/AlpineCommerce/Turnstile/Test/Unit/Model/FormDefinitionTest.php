<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Api\FormDefinitionInterface;
use AlpineCommerce\Turnstile\Model\FormDataPersister\FormDataPersisterInterface;
use AlpineCommerce\Turnstile\Model\FormDefinition;
use PHPUnit\Framework\TestCase;

class FormDefinitionTest extends TestCase
{
    public function testExposesConfiguredValues(): void
    {
        $persister = $this->createMock(FormDataPersisterInterface::class);
        $form = new FormDefinition(
            'customer_create',
            'Create an Account',
            ['customer_account_createpost'],
            'Magento_Customer',
            FormDefinitionInterface::FAILURE_RESPONSE_REDIRECT,
            'customer/account/create',
            ['_secure' => true],
            $persister,
            'recaptcha_frontend/type_for/customer_create',
            20
        );

        $this->assertSame('customer_create', $form->getId());
        $this->assertSame('Create an Account', $form->getLabel());
        $this->assertSame(['customer_account_createpost'], $form->getActions());
        $this->assertSame('Magento_Customer', $form->getRequiredModule());
        $this->assertSame(FormDefinitionInterface::FAILURE_RESPONSE_REDIRECT, $form->getFailureResponse());
        $this->assertSame('customer/account/create', $form->getRedirectPath());
        $this->assertSame(['_secure' => true], $form->getRedirectParams());
        $this->assertSame($persister, $form->getPersister());
        $this->assertSame('recaptcha_frontend/type_for/customer_create', $form->getRecaptchaConfigPath());
        $this->assertSame(20, $form->getSortOrder());
    }

    public function testDefaultsUseAutoResponseAndPreviousPage(): void
    {
        $form = new FormDefinition('newsletter', 'Newsletter Subscription', ['newsletter_subscriber_new']);

        $this->assertNull($form->getRequiredModule());
        $this->assertSame(FormDefinitionInterface::FAILURE_RESPONSE_AUTO, $form->getFailureResponse());
        $this->assertNull($form->getRedirectPath());
        $this->assertSame([], $form->getRedirectParams());
        $this->assertNull($form->getPersister());
        $this->assertNull($form->getRecaptchaConfigPath());
        $this->assertSame(100, $form->getSortOrder());
    }

    public function testActionsAreNormalisedToUniqueLowercase(): void
    {
        $form = new FormDefinition(
            'customer_login',
            'Customer Login',
            [' Customer_Account_LoginPost ', 'customer_account_loginpost']
        );

        $this->assertSame(['customer_account_loginpost'], $form->getActions());
    }

    public function testRejectsUnknownFailureResponse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown Turnstile failure response "html"');

        new FormDefinition('contact', 'Contact Us', ['contact_index_post'], null, 'html');
    }

    public function testRejectsEmptyActionList(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Turnstile form "contact" declares no action');

        new FormDefinition('contact', 'Contact Us', ['  ']);
    }
}
