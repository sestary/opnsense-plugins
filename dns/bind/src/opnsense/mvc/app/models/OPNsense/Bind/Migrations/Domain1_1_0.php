<?php

/**
 *    Copyright (C) 2022 Robbert Rijkse
 *
 *    All rights reserved.
 *
 *    Redistribution and use in source and binary forms, with or without
 *    modification, are permitted provided that the following conditions are met:
 *
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *    2. Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 *    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 *    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 *    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 *    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 *    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 *    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 *    POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace OPNsense\Bind\Migrations;

use OPNsense\Base\BaseModelMigration;
use OPNsense\Core\Config;
use OPNsense\Bind\Tsigkey;

class Domain1_1_0 extends BaseModelMigration
{
    /**
    * Migrate older keys into new model
    * @param $model
    */
    public function run($model)
    {
        $config = Config::getInstance()->object();

        # Checks to see if there is a bind config section, otherwise skips the rest of the migration
        if (empty($config->OPNsense->bind)) {
            return;
        }

        # This retrieves the existing keys just in case they exist
        $tsigkeyNames = [];
        $tsigHandle = new Tsigkey();
        foreach (($tsigHandle->getNodes())["tsigkeys"]["tsigkey"] as $tsigkeyuuid => $tsigkey) {
            $tsigkeyNames[$tsigkey["name"]] = $tsigkeyuuid;
        }

        $bindConfig = $config->OPNsense->bind;
        foreach ($bindConfig->domain->domains->domain as $domain) {
            $domainuuid = $domain->attributes()["uuid"];
            echo "Domain $domain->domainname found with $domainuuid\n";

            if (!empty($domain->transferkeyname)) {
                echo "Transfer key isn't empty";
                if (!array_key_exists((string)$domain->transferkeyname, $tsigkeyNames)) {
                    echo "Didn't find the key $domain->transferkeyname name already";

                    $realdomain = $model->getNodeByReference('domains.domain.' . $domainuuid);

                    $newkey = $tsigHandle->tsigkeys->tsigkey->Add();
                    $newkey->setNodes([
                       'enabled' => 1,
                        'algo' => $domain->transferkeyalgo,
                        'name' => $domain->transferkeyname,
                        'secret' => $domain->transferkey,
                    ]);
                    $newkeyuuid = $newkey->getAttributes()["uuid"];

                    if ((string)$realdomain->type == "master") {
                        $tsigKeyNode = $realdomain->allowtransfertsigkey->Add();
                    } else {
                        $tsigkeyNode = $realdomain->mastertransfertsigkey->Add();
                    }
                    $tsigkeyNode->setNodes($newkeyuuid);
                }
            }
        }        # Temporarily here for testing so the version number doesn't increase incase it does actually work
        trigger_error("Test",E_USER_ERROR);

        parent::run($model);
    }
}
