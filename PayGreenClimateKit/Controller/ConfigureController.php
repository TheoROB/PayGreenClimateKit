<?php

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PayGreenClimateKit\Controller;

use Http\Client\Curl\Client;
use PayGreenClimateKit\PayGreenClimateKit;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Event\Category\CategoryCreateEvent;
use Thelia\Core\Event\Product\ProductCreateEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Model\Category;
use Thelia\Model\CategoryQuery;
use Thelia\Model\Currency;
use Thelia\Model\Lang;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Model\TaxRuleQuery;
use Thelia\Tools\URL;

class ConfigureController extends BaseAdminController
{
    public function configure()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'paygreenClimatekit', AccessManager::UPDATE)) {
            return $response;
        }

        $configurationForm = $this->createForm('paygreenClimatekit_configuration');

        try {
            $form = $this->validateForm($configurationForm);

            // Get the form field values
            $data = $form->getData();

            foreach ($data as $name => $value) {
                if (\is_array($value)) {
                    $value = implode(';', $value);
                }

                PayGreenClimateKit::setConfigValue($name, $value);
            }

            // Log configuration modification
            $this->adminLogAppend(
                'paygreenClimatekit.configuration.message',
                AccessManager::UPDATE,
                'PayGreenClimateKit configuration updated'
            );

            // Redirect to the success URL,
            if ($this->getRequest()->get('save_mode') === 'stay') {
                // If we have to stay on the same page, redisplay the configuration page/
                $url = '/admin/module/PayGreenClimateKit';
            } else {
                // If we have to close the page, go back to the module back-office page.
                $url = '/admin/modules';
            }

            return $this->generateRedirect(URL::getInstance()->absoluteUrl($url));
        } catch (FormValidationException $ex) {
            $message = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            $message = $ex->getMessage();
        }
        $this->setupFormErrorContext(
            $this->getTranslator()->trans('PayGreenClimateKit configuration', [], PayGreenClimateKit::DOMAIN_NAME),
            $message,
            $configurationForm,
            $ex
        );

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/PayGreenClimateKit'));
    }

    public function downloadCatalog(): void
    {
        $locale = Lang::getDefaultLanguage()->getLocale();
        $currency = Currency::getDefaultCurrency();
        $pseList = ProductSaleElementsQuery::create()
            ->orderByRef()
            ->find();

        $response = new StreamedResponse();
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->setCallback(function () use ($currency, $locale, $pseList): void {
            $fh = fopen('php://output', 'w');
            $ligne = ['nom', 'ID-tech', 'code article', 'poids', 'prix hors taxe', 'categorie_1', 'categorie_2', 'categorie_3'];
            fputcsv($fh, $ligne);
            foreach ($pseList as $pse) {
                /** @var Category[] $pathCategory */
                $pathCategory = CategoryQuery::getPathToCategory($pse->getProduct()->getDefaultCategoryId());
                $ligne = [
                    $pse->getProduct()->setLocale($locale)->getTitle(),
                    $pse->getProduct()->getId(),
                    $pse->getRef() => preg_replace('/[^a-zA-Z0-9_-]+/', '_', $pse->getRef()),
                    $pse->getWeight(),
                    $pse->getPricesByCurrency($currency)->getPrice(),
                    isset($pathCategory[0]) ? $pathCategory[0]->setLocale($locale)->getTitle() : '',
                    isset($pathCategory[1]) ? $pathCategory[0]->setLocale($locale)->getTitle() : '',
                    isset($pathCategory[2]) ? $pathCategory[0]->setLocale($locale)->getTitle() : '',
                ];

                fputcsv($fh, $ligne);
                flush();
            }
            fclose($fh);
        });
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="catalog.csv"');
        $response->send();
    }

    public function activatePaygreen(): void
    {
        $accountName = PayGreenClimateKit::getConfigValue('accountName');
        $userName = PayGreenClimateKit::getConfigValue('userName');
        $password = PayGreenClimateKit::getConfigValue('password');
        $clientId = PayGreenClimateKit::getConfigValue('clientId');

        $curl = new Client();

        $environment = new \Paygreen\Sdk\Climate\V2\Environment(
            $clientId,
            'PRODUCTION',
            2
        );

        $environment->setTestMode(true);

        $client = new \Paygreen\Sdk\Climate\V2\Client($curl, $environment);

        $response = $client->login($accountName, $userName, $password);
        $responseData = json_decode($response->getBody()->getContents());
        dump($responseData);

        $client->setBearer($responseData->access_token);

        $response = $client->refresh('openstudio-toulouse', $responseData->refresh_token);
        $responseData = json_decode($response->getBody()->getContents());
        dump($responseData);

//        $client->setBearer($responseData->access_token);

        $response = $client->getCurrentUserInfos();
        $responseData = json_decode($response->getBody()->getContents());
        dump($responseData);

        $response = $client->createEmptyFootprint('my-footprint-id');
        $responseData = json_decode($response->getBody()->getContents());
        dump($responseData);
    }

    public function addContributionAction(): void
    {
        $price = $_GET['price'];
        $locale = Lang::getDefaultLanguage()->getLocale();
        $dispatcher = new EventDispatcher();

        // Créer automatiqsuement la catégorie
        $categoryCreateEvent = new CategoryCreateEvent();

        $categoryCreateEvent
            ->setParent(0)
            ->setLocale($locale)
            ->setVisible(false)
            ->setLocale('en_US')
            ->setTitle('Contribution')
            ->setLocale('fr_FR')
            ->setTitle('Contribution')
            ->setLocale('es_ES')
            ->setTitle('Contribución')
        ;

        $dispatcher->dispatch($categoryCreateEvent, TheliaEvents::CATEGORY_CREATE);

        $category = new Category();

        $category
            ->setParent(0)
            ->setLocale($locale)
            ->setVisible(false)
            ->setLocale('en_US')
            ->setTitle('Contribution')
            ->setLocale('fr_FR')
            ->setTitle('Contribution')
            ->setLocale('es_ES')
            ->setTitle('Contribución')
            ->save();

        $taxRuleId = TaxRuleQuery::create()->findOneByIsDefault(true)->getId();

        $currencyId = Currency::getDefaultCurrency()->getId();

        $createProductEvent = new ProductCreateEvent();
        $createProductEvent
            ->setRef('')
            ->setLocale($locale)
            ->setTitle('PaygreenContribution')
            ->setVisible(false)
            ->setVirtual(false)
            ->setTaxRuleId($taxRuleId)
            ->setDefaultCategory($category->getId())
            ->setBasePrice($price)
            ->setCurrencyId($currencyId)
            ->setBaseWeight(0);



        $dispatcher->dispatch($createProductEvent, TheliaEvents::PRODUCT_CREATE);
    }



    public function removeContributionAction(): void
    {
    }
}
