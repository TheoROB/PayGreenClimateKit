<?php

namespace PayGreenClimateKit\Controller;

use PayGreenClimateKit\PayGreenClimateKit;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Model\Category;
use Thelia\Model\CategoryQuery;
use Thelia\Model\Currency;
use Thelia\Model\Lang;
use Thelia\Model\LangQuery;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Tools\URL;

class ConfigureController extends BaseAdminController
{
    public function configure()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'paygreenClimatekit', AccessManager::UPDATE)) {
            return $response;
        }

        $configurationForm = $this->createForm('paygreenClimatekit_configuration');
        $message = null;

        try {
            $form = $this->validateForm($configurationForm);

            // Get the form field values
            $data = $form->getData();

            foreach ($data as $name => $value) {
                if (is_array($value)) {
                    $value = implode(';', $value);
                }

                PayGreenClimateKit::setConfigValue($name, $value);
            }


            // Log configuration modification
            $this->adminLogAppend(
                "paygreenClimatekit.configuration.message",
                AccessManager::UPDATE,
                "PayGreenClimateKit configuration updated"
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
            $this->getTranslator()->trans("PayGreenClimateKit configuration", [], PayGreenClimateKit::DOMAIN_NAME),
            $message,
            $configurationForm,
            $ex
        );

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/PayGreenClimateKit'));
    }

    public function downloadCatalog()
    {
        $locale = Lang::getDefaultLanguage()->getLocale();
        $currency = Currency::getDefaultCurrency();
        $pseList = ProductSaleElementsQuery::create()
            ->orderByRef()
            ->find();

        $response = new StreamedResponse();
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->setCallback(function () use ($currency, $locale, $pseList) {
            $fh = fopen("php://output", 'wb');
            $ligne = ['nom','ID-tech','code article','poids','prix hors taxe','categorie_1','categorie_2','categorie_3'];
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
}
