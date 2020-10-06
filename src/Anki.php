<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect;

use Psr\Http\Client\ClientExceptionInterface;
use YaSD\AnkiConnect\Exception\ActionFailedException;
use YaSD\AnkiConnect\Exception\ApiErrorException;
use YaSD\AnkiConnect\Exception\NotFoundException;
use YaSD\AnkiConnect\Exception\UnexpectedResultException;

class Anki
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->checkApiVersion();
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(string $host = '127.0.0.1', int $port = 8765, int $version = 6): self
    {
        $client = new Client($host, $port, $version);
        return new static($client);
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    protected function checkApiVersion(): void
    {
        /** make sure that your application and AnkiConnect are able to communicate properly with each other. New versions of AnkiConnect are backwards compatible; as long as you are using actions which are available in the reported AnkiConnect version or earlier, everything should work fine */
        if ($this->client->getVersion() > ($apiVersion = $this->getApiVersion())) {
            throw new \LogicException("Unsupported_Version: AnkiConnect_v{$apiVersion}");
        }
    }

    /**
     * get AnkiConnect API version
     *
     * @return int AnkiConnect API version number
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getApiVersion(): int
    {
        return $this->client->api('version', null, 'is_int');
    }

    /**
     * retrieve the list of profiles
     *
     * @return string[] all profile's names
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getAllProfiles(): array
    {
        return $this->client->api('getProfiles', null, 'is_array');
    }

    /**
     * selects the profile specified in request
     *
     * @param string $profileName
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws ActionFailedException if failed
     */
    public function loadProfile(string $profileName): self
    {
        $result = $this->client->api('loadProfile', [
            'name' => $profileName
        ], 'is_bool');

        ActionFailedException::throwWhenFalse($result, "Load_Profile_Failed: {$profileName}");
        return $this;
    }

    /**
     * synchronizes the local Anki collections with AnkiWeb
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function syncCollection(): self
    {
        $this->client->api('sync', null, 'is_null');
        return $this;
    }

    /**
     * tells anki to reload all data from the database
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function reloadCollection(): self
    {
        $this->client->api('reloadCollection', null, 'is_null');
        return $this;
    }

    /**
     * imports a file in .apkg format into the collection
     *
     * Note that the file path is relative to Anki's collection.media folder, not to the client
     *
     * @param string $apkgPath .apkg file path
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws ActionFailedException if failed
     */
    public function importApkg(string $apkgPath): self
    {
        $result = $this->client->api('importPackage', [
            'path' => $apkgPath,
        ], 'is_bool');

        ActionFailedException::throwWhenFalse($result, "Import_Apkg_Failed: {$apkgPath}");
        return $this;
    }

    /**
     * exports a given deck in .apkg format
     *
     * @param string $deckname deck name
     * @param string $apkgPath .apkg file path
     * @param bool $includeSched include the cards' scheduling data
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws ActionFailedException if failed
     */
    public function exportApkg(string $deckname, string $apkgPath, bool $includeSched = false): self
    {
        $result = $this->client->api('exportPackage', [
            'deck' => $deckname,
            'path' => $apkgPath,
            'includeSched' => $includeSched,
        ], 'is_bool');

        ActionFailedException::throwWhenFalse($result, "Export_Apkg_Failed: {$deckname}: {$apkgPath}");
        return $this;
    }

    /**
     * get all model names
     *
     * @return string[]
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getAllModels(): array
    {
        return $this->client->api('modelNames', null, 'is_array');
    }

    /**
     * get model's fields
     *
     * @param string $modelName
     *
     * @return string[]
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getModelFields(string $modelName): array
    {
        return $this->client->api('modelFieldNames', [
            'modelName' => $modelName,
        ], 'is_array');
    }

    /**
     * get model's CSS styling
     *
     * @param string $modelName
     *
     * @return string
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getModelCss(string $modelName): string
    {
        $result = $this->client->api('modelStyling', [
            'modelName' => $modelName,
        ], function ($result) {
            return ($result instanceof \stdClass) and isset($result->css);
        });
        return $result->css;
    }

    /**
     * get model's all templates
     *
     * @param string $modelName
     *
     * @return Template[]
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getModelTemplates(string $modelName): array
    {
        $result = $this->client->api('modelTemplates', [
            'modelName' => $modelName,
        ], function ($result) {
            return $result instanceof \stdClass;
        });

        $templates = [];
        foreach ($result as $name => $template) {
            $templates[] = new Template($name, $template->{'Front'}, $template->{'Back'});
        }
        return $templates;
    }

    /**
     * update model's CSS styling
     *
     * @param string $modelName
     * @param string $css
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function updateModelCss(string $modelName, string $css): self
    {
        $this->client->api('updateModelStyling', [
            'model' => [
                'name' => $modelName,
                'css' => $css,
            ],
        ], 'is_null');
        return $this;
    }

    /**
     * update model's template
     *
     * @param string $modelName
     * @param Template $template
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function updateModelTemplate(string $modelName, Template $template): self
    {
        $this->client->api('updateModelTemplates', [
            'model' => [
                'name' => $modelName,
                'templates' => [
                    $template->getName() => [
                        'Front' => $template->getFront(),
                        'Back' => $template->getBack(),
                    ],
                ],
            ],
        ], 'is_null');
        return $this;
    }

    /**
     * create a model
     *
     * @param Model $model
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function createModel(Model $model): self
    {
        $request = [
            'modelName' => $model->getName(),
            'inOrderFields' => $model->getFields(),
        ];

        if ($model->getCss()) {
            $request['css'] = $model->getCss();
        }

        $request['cardTemplates'] = [];
        foreach ($model->getTemplates() as $template) {
            $request['cardTemplates'][] = [
                'Name' => $template->getName(),
                'Front' => $template->getFront(),
                'Back' => $template->getBack(),
            ];
        }

        $this->client->api('createModel', $request, function ($result) {
            return ($result instanceof \stdClass) and isset($result->id) and \is_numeric($result->id);
        });
        return $this;
    }

    /**
     * save a media file inside the media folder, from base64-encoded data
     *
     * To prevent Anki from removing files not used by any cards, prefix the filename with an underscore
     *
     * @param string $filename media file name
     * @param string $base64 base64-encoded data
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function saveMediaFromBase64(string $filename, string $base64): self
    {
        $this->client->api('storeMediaFile', [
            'filename' => $filename,
            'data' => $base64,
        ], 'is_null');
        return $this;
    }

    /**
     * save a media file inside the media folder, from download url
     *
     * To prevent Anki from removing files not used by any cards, prefix the filename with an underscore
     *
     * @param string $filename media file name
     * @param string $url download url
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function saveMediaFromUrl(string $filename, string $url): self
    {
        $this->client->api('storeMediaFile', [
            'filename' => $filename,
            'url' => $url,
        ], 'is_null');
        return $this;
    }

    /**
     * retrieves the base64-encoded contents of the specified media file
     *
     * @param string $filename media file name
     *
     * @return string base64-encoded contents
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws NotFoundException media file not exist
     */
    public function getMediaContent(string $filename): string
    {
        $result = $this->client->api('retrieveMediaFile', [
            'filename' => $filename,
        ], function ($result) {
            return \is_string($result) or ($result === false);
        });

        if ($result === false) {
            throw new NotFoundException("Media_Not_Exist: {$filename}");
        }

        return $result;
    }

    /**
     * deletes the specified file inside the media folder
     *
     * @param string $filename media file name
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function deleteMedia(string $filename): self
    {
        $this->client->api('deleteMediaFile', [
            'filename' => $filename,
        ], 'is_null');
        return $this;
    }

    /**
     * get all deck names
     *
     * @return string[] all deck names
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getAllDecks(): array
    {
        return $this->client->api('deckNames', null, 'is_array');
    }

    /**
     * create a new empty deck
     *
     * Will not overwrite a deck that exists with the same name
     *
     * @param string $deckName
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function createDeck(string $deckName): self
    {
        $this->client->api('createDeck', [
            'deck' => $deckName,
        ], 'is_numeric');
        return $this;
    }

    /**
     * move cards to another deck, creating the deck if it doesn't exist yet
     *
     * @param int[] $cardIds
     * @param string $deckName
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function moveCards(array $cardIds, string $deckName): self
    {
        $this->client->api('changeDeck', [
            'cards' => $cardIds,
            'deck' => $deckName,
        ], 'is_null');
        return $this;
    }

    /**
     * delete deck
     *
     * @param string $deckName
     * @param bool $cardsToo if true, the cards within the deleted decks will also be deleted; otherwise they will be moved to the default deck
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function deleteDeck(string $deckName, bool $cardsToo = false): self
    {
        $this->client->api('deleteDecks', [
            'decks' => [$deckName],
            'cardsToo' => $cardsToo,
        ], 'is_null');
        return $this;
    }

    /**
     * get deck's config object
     *
     * @param string $deckName
     *
     * @return Config
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getDeckConfig(string $deckName): Config
    {
        $config = $this->client->api('getDeckConfig', [
            'deck' => $deckName,
        ], function ($result) {
            return ($result instanceof \stdClass) and isset($result->id) and \is_int($result->id);
        });

        return new Config($config);
    }

    /**
     * set deck's config group ID
     *
     * @param string $deckName
     * @param int $configId
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws ActionFailedException if deck or config not exist
     */
    public function setDeckConfig(string $deckName, int $configId): self
    {
        $result = $this->client->api('setDeckConfigId', [
            'decks' => [$deckName],
            'configId' => $configId,
        ], 'is_bool');

        ActionFailedException::throwWhenFalse($result, "Set_Deck_Config_Failed: {$deckName}: {$configId}");
        return $this;
    }

    /**
     * update config group
     *
     * @param Config $config
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws ActionFailedException if failed
     */
    public function updateConfig(Config $config): self
    {
        $result = $this->client->api('saveDeckConfig', [
            'config' => $config->getConfig(),
        ], 'is_bool');

        ActionFailedException::throwWhenFalse($result, "Update_Config_Failed: {$config->getId()}");
        return $this;
    }

    /**
     * clone from config group, create new config group with given name
     *
     * @param int $configId clone from
     * @param string $newConfigName new config group name
     *
     * @return int config Id of new config group
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function cloneConfig(int $configId, string $newConfigName): int
    {
        return $this->client->api('cloneDeckConfigId', [
            'name' => $newConfigName,
            'cloneFrom' => $configId,
        ], 'is_int');
    }

    /**
     * remove config group
     *
     * @param int $configId
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws ActionFailedException if attempting to remove either the default configuration group (ID = 1) or a configuration group that does not exist
     */
    public function deleteConfig(int $configId): self
    {
        $result = $this->client->api('removeDeckConfigId', [
            'configId' => $configId,
        ], 'is_bool');

        ActionFailedException::throwWhenFalse($result, "Remove_Config_Failed: {$configId}");
        return $this;
    }

    /**
     * get cards' ease factors
     *
     * @param int[] $cardIds
     *
     * @return int[] cardId as key, ease factor as value
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getCardsFactors(array $cardIds): array
    {
        $ret = $this->client->api('getEaseFactors', [
            'cards' => $cardIds,
        ], 'is_array');

        $factors = [];
        foreach ($cardIds as $k => $cardId) {
            $factors[$cardId] = $ret[$k];
        }
        return $factors;
    }

    /**
     * get card's ease factor
     *
     * @param int $cardId
     *
     * @return int
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getCardFactor(int $cardId): int
    {
        return $this->getCardsFactors([$cardId])[$cardId];
    }

    /**
     * set cards' ease factors
     *
     * @param int[] $cardIds
     * @param int[] $factors
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function setCardsFactors(array $cardIds, array $factors): self
    {
        $this->client->api('setEaseFactors', [
            'cards' => $cardIds,
            'easeFactors' => $factors,
        ], 'is_array');
        return $this;
    }

    /**
     * set card's ease factor
     *
     * @param int $cardId
     * @param int $factor
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function setCardFactor(int $cardId, int $factor): self
    {
        return $this->setCardsFactors([$cardId], [$factor]);
    }

    /**
     * suspend cards
     *
     * @param int[] $cardIds
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function suspendCards(array $cardIds): self
    {
        $this->client->api('suspend', [
            'cards' => $cardIds,
        ], 'is_bool');
        return $this;
    }

    /**
     * unsuspend cards
     *
     * @param int[] $cardIds
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function unsuspendCards(array $cardIds): self
    {
        $this->client->api('unsuspend', [
            'cards' => $cardIds,
        ], 'is_bool');
        return $this;
    }

    /**
     * whether cards are suspended
     *
     * @param int[] $cardIds
     *
     * @return bool[] cardId as key, bool as value
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function areCardsSuspended(array $cardIds): array
    {
        $bools = $this->client->api('areSuspended', [
            'cards' => $cardIds,
        ], 'is_array');

        $suspends = [];
        foreach ($cardIds as $k => $cardId) {
            $suspends[$cardId] = $bools[$k];
        }
        return $suspends;
    }

    /**
     * is card suspended
     *
     * @param int $cardId
     *
     * @return bool
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function isCardSuspended(int $cardId): bool
    {
        return $this->areCardsSuspended([$cardId])[$cardId];
    }

    /**
     * whether cards are due
     *
     * Note: cards in the learning queue with a large interval (over 20 minutes) are treated as not due until the time of their interval has passed, to match the way Anki treats them when reviewing
     *
     * @param int[] $cardIds
     *
     * @return bool[] cardId as key, bool as value
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function areCardsDue(array $cardIds): array
    {
        $bools = $this->client->api('areDue', [
            'cards' => $cardIds,
        ], 'is_array');

        $dues = [];
        foreach ($cardIds as $k => $cardId) {
            $dues[$cardId] = $bools[$k];
        }
        return $dues;;
    }

    /**
     * is card due
     *
     * @param int $cardId
     *
     * @return bool
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function isCardDue(int $cardId): bool
    {
        return $this->areCardsDue([$cardId])[$cardId];
    }

    /**
     * all intervals for cards
     *
     * @param int[] $cardIds
     *
     * @return int[][] cardId for key, intervals for value (in seconds)
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getCardsIntervals(array $cardIds): array
    {
        $intervals = $this->client->api('getIntervals', [
            'cards' => $cardIds,
            'complete' => true,
        ], 'is_array');

        $ret = [];
        foreach ($cardIds as $k => $cardId) {
            foreach ($intervals[$k] as $interval) {
                // Negative intervals are in seconds and positive intervals in days
                @$ret[$cardId][] = ($interval < 0) ? \abs($interval) : ($interval * 86400);
            }
        }
        return $ret;
    }

    /**
     * get card's all intervals
     *
     * @param int $cardId
     *
     * @return int[] intervals in seconds
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getCardIntervals(int $cardId): array
    {
        return $this->getCardsIntervals([$cardId])[$cardId];
    }

    /**
     * get card IDs for a given query
     *
     * @param string $query
     *
     * @return int[] card IDs
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function findCards(string $query): array
    {
        return $this->client->api('findCards', [
            'query' => $query,
        ], 'is_array');
    }

    /**
     * get cards' detail objects
     *
     * @param int[] $cardIds
     *
     * @return array[] cardId for key, Card object for value
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws NotFoundException if any of cards not exist
     */
    public function getCards(array $cardIds): array
    {
        $ret = $this->client->api('cardsInfo', [
            'cards' => $cardIds,
        ], 'is_array');

        $cards = [];
        foreach ($ret as $card) {
            $cardId = $card->cardId;
            $cards[$cardId] = (array) $card;
        }

        if ($cardIdsNotExist = \array_diff($cardIds, \array_keys($cards))) {
            throw new NotFoundException("Card_Not_Found: " . \implode(', ', $cardIdsNotExist));
        }
        return $cards;
    }

    /**
     * get card's detail array
     *
     * @param int $cardId
     *
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws NotFoundException if not exist
     */
    public function getCard(int $cardId): array
    {
        return $this->getCards([$cardId])[$cardId];
    }

    /**
     * get the count of cards that have been reviewed in the current day (with day start time as configured by user in anki)
     *
     * @return int
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getReviewCountToday(): int
    {
        return $this->client->api('getNumCardsReviewedToday', null, 'is_int');
    }

    /**
     * get all card reviews of deck since a certain time
     *
     * @param string $deckName
     * @param int $startTimeWithMs time with microseconds (13 digit)
     *
     * @return Review[]
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getReviewsOfDeck(string $deckName, int $startTimeWithMs): array
    {
        $reviews = $this->client->api('cardReviews', [
            'deck' => $deckName,
            'startID' => $startTimeWithMs,
        ], 'is_array');

        return \array_map(function ($reviewArr) {
            return new Review(...$reviewArr);
        }, $reviews);
    }

    /**
     * return last review time (with microseconds) for the given deck
     *
     * @param string $deckName
     *
     * @return int last review time with microseconds (13 digit). 0 if no review has ever been made for the deck
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getLastReviewTimeOfDeck(string $deckName): int
    {
        return $this->client->api('getLatestReviewID', [
            'deck' => $deckName,
        ], 'is_int');
    }

    /**
     * add new reviews into database
     *
     * @param Review[] $reviews
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function addReviews(array $reviews): self
    {
        $request = ['reviews' => []];
        foreach ($reviews as $review) {
            $request['reviews'][] = $review->toArray();
        }

        $this->client->api('insertReviews', $request, 'is_null');
        return $this;
    }

    /**
     * invokes the Card Browser dialog and searches for a given query
     *
     * @param string $query
     *
     * @return int[] cardIds
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function guiFindCards(string $query): array
    {
        return $this->client->api('guiBrowse', [
            'query' => $query,
        ], 'is_array');
    }

    /**
     * invokes the Add Cards dialog, presets the note using the given deck and model, with the provided field values and tags
     *
     * Invoking it multiple times closes the old window and reopen the window with the new provided values
     *
     * The closeAfterAdding member inside options group can be set to true to create a dialog that closes upon adding the note. Invoking the action mutliple times with this option will create multiple windows
     *
     * @param Note $note
     *
     * @return int new noteId
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function guiAddNote(Note $note): int
    {
        return $this->client->api('guiAddCards', [
            'note' => $note->toArray(),
        ], 'is_int');
    }

    /**
     * GUI: get current card's detail array
     *
     * @return array current card's detail array
     * @return null if not in review mode
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function guiGetCurrentCard(): ?array
    {
        $card = $this->client->api('guiCurrentCard', null, function ($result) {
            return ($result instanceof \stdClass) or \is_null($result);
        });

        return ($card ? (array) $card : null);
    }

    /**
     * GUI: show question for current card
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws ActionFailedException if failed
     */
    public function guiShowCurrentQuestion(): self
    {
        $result = $this->client->api('guiShowQuestion', null, 'is_bool');

        ActionFailedException::throwWhenFalse($result, "GUI_Show_Question_Failed");
        return $this;
    }

    /**
     * GUI: show answer for current card
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws ActionFailedException if failed
     */
    public function guiShowCurrentAnswer(): self
    {
        $result = $this->client->api('guiShowAnswer', null, 'is_bool');

        ActionFailedException::throwWhenFalse($result, "GUI_Show_Answer_Failed");
        return $this;
    }

    /**
     * answer current card
     *
     * Note that the answer for the current card must be displayed before before any answer can be accepted by Anki
     *
     * @param int $ease
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws ActionFailedException
     */
    public function guiAnswerCurrentCard(int $ease): self
    {
        $result = $this->client->api('guiAnswerCard', [
            'ease' => $ease,
        ], 'is_bool');

        ActionFailedException::throwWhenFalse($result, "Answer_Current_Card_Failed");
        return $this;
    }

    /**
     * request to gracefully close Anki
     *
     * This operation is asynchronous, so it will return immediately and won't wait until the Anki process actually terminates
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function guiExitAnki(): self
    {
        $this->client->api('guiExitAnki', null, 'is_null');
        return $this;
    }

    /**
     * add one note
     *
     * @param Note $note
     *
     * @return int new noteId
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws ActionFailedException if failed
     */
    public function addNote(Note $note): int
    {
        $noteId = $this->client->api('addNote', [
            'note' => $note->toArray(),
        ], function ($result) {
            return \is_int($result) or \is_null($result);
        });

        ActionFailedException::throwWhenFalse((bool) $noteId, "Add_Note_Failed: " . \json_encode($note));
        return $noteId;
    }

    /**
     * update note's fields
     *
     * You can also include audio files which will be added to the note with an optional audio property
     *
     * @param int $noteId
     * @param array $fields
     * @param array|null $audios
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function updateNote(int $noteId, array $fields, array $audios = null): self
    {
        $request = [
            'note' => [
                'id' => $noteId,
                'fields' => $fields,
            ]
        ];
        if ($audios) {
            $request['note']['audio'] = $audios;
        }
        $this->client->api('updateNoteFields', $request, 'is_null');
        return $this;
    }

    /**
     * add notes' tag
     *
     * @param string $tagName
     * @param int[] $noteIds
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function tagNotes(string $tagName, array $noteIds): self
    {
        $this->client->api('addTags', [
            'notes' => $noteIds,
            'tags' => $tagName,
        ], 'is_null');
        return $this;
    }

    /**
     * remove notes' tag
     *
     * @param string $tagName
     * @param int[] $noteIds
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function untagNotes(string $tagName, array $noteIds): self
    {
        $this->client->api('removeTags', [
            'notes' => $noteIds,
            'tags' => $tagName,
        ], 'is_null');
        return $this;
    }

    /**
     * get all tag names
     *
     * @return string[] tag names
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function getAllTags(): array
    {
        return $this->client->api('getTags', null, 'is_array');
    }

    /**
     * find note's IDs for a given query
     *
     * @param string $query
     *
     * @return int[] noteIds
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function findNotes(string $query): array
    {
        return $this->client->api('findNotes', [
            'query' => $query,
        ], 'is_array');
    }

    /**
     * get notes' detail array
     *
     * @param int[] $noteIds
     *
     * @return array[]
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws NotFoundException if any noteId not exist
     */
    public function getNotes(array $noteIds): array
    {
        $ret = $this->client->api('notesInfo', [
            'notes' => $noteIds,
        ], 'is_array');

        $notes = [];
        foreach ($ret as $note) {
            $noteId = $note->noteId;
            $notes[$noteId] = (array) $note;
        }

        if ($noteIdsNotExist = \array_diff($noteIds, \array_keys($notes))) {
            throw new NotFoundException("Note_Not_Exist: " . \implode(', ', $noteIdsNotExist));
        }
        return $notes;
    }

    /**
     * get note's detail array
     *
     * @param int $noteId
     *
     * @return array
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     * @throws NotFoundException if noteId not exist
     */
    public function getNote(int $noteId): array
    {
        return $this->getNotes([$noteId])[$noteId];
    }

    /**
     * delete notes
     *
     * If a note has several cards associated with it, all associated cards will be deleted
     *
     * @param int[] $noteIds
     *
     * @return self
     *
     * @throws ClientExceptionInterface
     * @throws ApiErrorException
     * @throws UnexpectedResultException
     */
    public function deleteNotes(array $noteIds): self
    {
        $this->client->api('deleteNotes', [
            'notes' => $noteIds,
        ], 'is_null');
        return $this;
    }
}
