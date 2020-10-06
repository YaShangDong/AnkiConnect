<?php

declare(strict_types=1);

namespace YaSD\AnkiConnect\Tests;

use YaSD\AnkiConnect\Anki;
use YaSD\AnkiConnect\Client;
use YaSD\AnkiConnect\Config;
use YaSD\AnkiConnect\Model;
use YaSD\AnkiConnect\Note;
use YaSD\AnkiConnect\Review;
use YaSD\AnkiConnect\Template;
use YaSD\AnkiConnect\Exception\NotFoundException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AnkiTest extends TestCase
{
    public function testConstructAndGetClient()
    {
        $anki = $this->getMockBuilder(Anki::class)
            ->onlyMethods(['getApiVersion'])
            ->disableOriginalConstructor()
            ->getMock();
        $anki->method('getApiVersion')->willReturn(6);
        $client = new Client();
        /** @var Anki $anki */

        $anki->__construct($client);
        $this->assertSame($client, $anki->getClient());
    }

    public function testConstructExceptionWhenVersionNotCompatible()
    {
        $anki = $this->getMockBuilder(Anki::class)
            ->onlyMethods(['getApiVersion'])
            ->disableOriginalConstructor()
            ->getMock();
        $anki->method('getApiVersion')->willReturn(6);
        $client = new Client('127.0.0.1', 8765, 7);
        /** @var Anki $anki */

        $this->expectException(\LogicException::class);
        $anki->__construct($client);
    }

    public function testGetApiVersion()
    {
        $request = '{"action":"version","version":6}';
        $response = '{"result":6,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $version = $anki->getApiVersion();
        $this->assertSame(6, $version);
    }

    public function testGetAllProfiles()
    {
        $request = '{"action":"getProfiles","version":6}';
        $response = '{"result":["User 1"],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame(['User 1'], $anki->getAllProfiles());
    }

    public function testLoadProfile()
    {
        $request = '{"action":"loadProfile","version":6,"params":{"name":"user1"}}';
        $response = '{"result":true,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->loadProfile('user1'));
    }

    public function testSyncCollection()
    {
        $request = '{"action":"sync","version":6}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->syncCollection());
    }

    public function testReloadCollection()
    {
        $request = '{"action":"reloadCollection","version":6}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->reloadCollection());
    }

    public function testImportApkg()
    {
        $request = '{"action":"importPackage","version":6,"params":{"path":"/data/Deck.apkg"}}';
        $response = '{"result":true,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->importApkg('/data/Deck.apkg'));
    }

    public function testExportApkg()
    {
        $request = '{"action":"exportPackage","version":6,"params":{"deck":"Default","path":"/data/Deck.apkg","includeSched":true}}';
        $response = '{"result":true,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->exportApkg('Default', '/data/Deck.apkg', true));
    }

    public function testGetAllModels()
    {
        $request = '{"action":"modelNames","version":6}';
        $response = '{"result":["Basic","Basic (and reversed card)"],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $models = $anki->getAllModels();
        $this->assertSame(["Basic", "Basic (and reversed card)"], $models);
    }

    public function testGetModelFields()
    {
        $request = '{"action":"modelFieldNames","version":6,"params":{"modelName":"Basic"}}';
        $response = '{"result":["Front","Back"],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $fields = $anki->getModelFields('Basic');
        $this->assertSame(['Front', 'Back'], $fields);
    }

    public function testGetModelCss()
    {
        $request = '{"action":"modelStyling","version":6,"params":{"modelName":"Basic (and reversed card)"}}';
        $response = '{"result":{"css":".card {\n font-family: arial;\n font-size: 20px;\n text-align: center;\n color: black;\n background-color: white;\n}\n"},"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $css = $anki->getModelCss('Basic (and reversed card)');
        $this->assertSame(".card {\n font-family: arial;\n font-size: 20px;\n text-align: center;\n color: black;\n background-color: white;\n}\n", $css);
    }

    public function testGetModelTemplates()
    {
        $request = '{"action":"modelTemplates","version":6,"params":{"modelName":"Basic (and reversed card)"}}';
        $response = '{"result":{"Card 1":{"Front":"{{Front}}","Back":"{{FrontSide}}\n\n<hr id=answer>\n\n{{Back}}"},"Card 2":{"Front":"{{Back}}","Back":"{{FrontSide}}\n\n<hr id=answer>\n\n{{Front}}"}},"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $templates = $anki->getModelTemplates('Basic (and reversed card)');
        $this->assertContainsOnlyInstancesOf(Template::class, $templates);
        $this->assertCount(2, $templates);
        $this->assertSame('Card 1', $templates[0]->getName());
    }

    public function testUpdateModelCss()
    {
        $request = '{"action":"updateModelStyling","version":6,"params":{"model":{"name":"Custom","css":"p { color: blue; }"}}}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->updateModelCss('Custom', 'p { color: blue; }'));
    }

    public function testUpdateModelTemplate()
    {
        $request = '{"action":"updateModelTemplates","version":6,"params":{"model":{"name":"Custom","templates":{"Card 1":{"Front":"{{Question}}?","Back":"{{Answer}}!"}}}}}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $template = new Template('Card 1', '{{Question}}?', '{{Answer}}!');
        $this->assertSame($anki, $anki->updateModelTemplate('Custom', $template));
    }

    public function testCreateModel()
    {
        $request = '{"action":"createModel","version":6,"params":{"modelName":"newModelName","inOrderFields":["Field1","Field2","Field3"],"css":"Optional CSS with default to builtin css","cardTemplates":[{"Name":"My Card 1","Front":"Front html {{Field1}}","Back":"Back html {{Field2}}"}]}}';
        $response = '{"result":{"sortf":0,"did":1,"mod":1551462107,"usn":-1,"vers":[],"type":0,"css":".card {\n font-family: arial;\n font-size: 20px;\n text-align: center;\n color: black;\n background-color: white;\n}\n","name":"TestApiModel","flds":[{"name":"Field1","ord":0,"sticky":false,"rtl":false,"font":"Arial","size":20,"media":[]},{"name":"Field2","ord":1,"sticky":false,"rtl":false,"font":"Arial","size":20,"media":[]}],"tmpls":[{"name":"My Card 1","ord":0,"qfmt":"","afmt":"This is the back of the card {{Field2}}","did":null,"bqfmt":"","bafmt":""}],"tags":[],"id":"1551462107104","req":[[0,"none",[]]]},"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $templates = [
            new Template("My Card 1", "Front html {{Field1}}", "Back html {{Field2}}"),
        ];
        $model = (new Model('newModelName', ["Field1", "Field2", "Field3"], $templates))->setCss('Optional CSS with default to builtin css');
        $this->assertSame($anki, $anki->createModel($model));
    }

    public function testSaveMediaFromBase64()
    {
        $request = '{"action":"storeMediaFile","version":6,"params":{"filename":"_hello.txt","data":"SGVsbG8sIHdvcmxkIQ=="}}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $ret = $anki->saveMediaFromBase64('_hello.txt', \base64_encode('Hello, world!'));
        $this->assertSame($anki, $ret);
    }

    public function testSaveMediaFromUrl()
    {
        $request = '{"action":"storeMediaFile","version":6,"params":{"filename":"_hello.txt","url":"https://url.to.file"}}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $ret = $anki->saveMediaFromUrl('_hello.txt', 'https://url.to.file');
        $this->assertSame($anki, $ret);
    }

    public function testGetMediaContent()
    {
        $request = '{"action":"retrieveMediaFile","version":6,"params":{"filename":"_hello.txt"}}';
        $response = '{"result":"SGVsbG8sIHdvcmxkIQ==","error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $base64 = $anki->getMediaContent('_hello.txt');
        $this->assertSame('Hello, world!', \base64_decode($base64));
    }

    public function testGetMediaContentExceptionWhenFileNotExist()
    {
        $request = '{"action":"retrieveMediaFile","version":6,"params":{"filename":"_hello.txt"}}';
        $response = '{"result":false,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->expectException(NotFoundException::class);
        $anki->getMediaContent('_hello.txt');
    }

    public function testDeleteMedia()
    {
        $request = '{"action":"deleteMediaFile","version":6,"params":{"filename":"_hello.txt"}}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $ret = $anki->deleteMedia('_hello.txt');
        $this->assertSame($anki, $ret);
    }

    public function testGetAllDecks()
    {
        $request = '{"action":"deckNames","version":6}';
        $response = '{"result":["Default"],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $decks = $anki->getAllDecks();
        $this->assertSame(['Default'], $decks);
    }

    public function testCreateDeck()
    {
        $request = '{"action":"createDeck","version":6,"params":{"deck":"Japanese::Tokyo"}}';
        $response = '{"result":1519323742721,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->createDeck('Japanese::Tokyo'));
    }

    public function testMoveCards()
    {
        $request = '{"action":"changeDeck","version":6,"params":{"cards":[1502098034045,1502098034048,1502298033753],"deck":"Japanese::JLPT N3"}}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->moveCards([1502098034045, 1502098034048, 1502298033753], 'Japanese::JLPT N3'));
    }

    public function testDeleteDeck()
    {
        $request = '{"action":"deleteDecks","version":6,"params":{"decks":["Japanese::JLPT N5"],"cardsToo":true}}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->deleteDeck('Japanese::JLPT N5', true));
    }

    public function testGetDeckConfig()
    {
        $request = '{"action":"getDeckConfig","version":6,"params":{"deck":"Default"}}';
        $response = '{"result":{"lapse":{"leechFails":8,"delays":[10],"minInt":1,"leechAction":0,"mult":0},"dyn":false,"autoplay":true,"mod":1502970872,"id":1,"maxTaken":60,"new":{"bury":true,"order":1,"initialFactor":2500,"perDay":20,"delays":[1,10],"separate":true,"ints":[1,4,7]},"name":"Default","rev":{"bury":true,"ivlFct":1,"ease4":1.3,"maxIvl":36500,"perDay":100,"minSpace":1,"fuzz":0.05},"timer":0,"replayq":true,"usn":-1},"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $config = $anki->getDeckConfig('Default');
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testSetDeckConfig()
    {
        $request = '{"action":"setDeckConfigId","version":6,"params":{"decks":["Default"],"configId":1}}';
        $response = '{"result":true,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->setDeckConfig('Default', 1));
    }

    public function testUpdateConfig()
    {
        $request = '{"action":"saveDeckConfig","version":6,"params":{"config":{"lapse":{"leechFails":8,"delays":[10],"minInt":1,"leechAction":0,"mult":0},"dyn":false,"autoplay":true,"mod":1502970872,"id":1,"maxTaken":60,"new":{"bury":true,"order":1,"initialFactor":2500,"perDay":20,"delays":[1,10],"separate":true,"ints":[1,4,7]},"name":"Default","rev":{"bury":true,"ivlFct":1,"ease4":1.3,"maxIvl":36500,"perDay":100,"minSpace":1,"fuzz":0.05},"timer":0,"replayq":true,"usn":-1}}}';
        $response = '{"result":true,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $config = \json_decode('{"lapse":{"leechFails":8,"delays":[10],"minInt":1,"leechAction":0,"mult":0},"dyn":false,"autoplay":true,"mod":1502970872,"id":1,"maxTaken":60,"new":{"bury":true,"order":1,"initialFactor":2500,"perDay":20,"delays":[1,10],"separate":true,"ints":[1,4,7]},"name":"Default","rev":{"bury":true,"ivlFct":1,"ease4":1.3,"maxIvl":36500,"perDay":100,"minSpace":1,"fuzz":0.05},"timer":0,"replayq":true,"usn":-1}');
        $config = new Config($config);
        $this->assertSame($anki, $anki->updateConfig($config));
    }

    public function testCloneConfig()
    {
        $request = '{"action":"cloneDeckConfigId","version":6,"params":{"name":"Copy of Default","cloneFrom":1}}';
        $response = '{"result":1502972374573,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $cloneId = $anki->cloneConfig(1, 'Copy of Default');
        $this->assertSame(1502972374573, $cloneId);
    }

    public function testDeleteConfig()
    {
        $request = '{"action":"removeDeckConfigId","version":6,"params":{"configId":1502972374573}}';
        $response = '{"result":true,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->deleteConfig(1502972374573));
    }

    public function testGetCardsFactors()
    {
        $request = '{"action":"getEaseFactors","version":6,"params":{"cards":[1483959291685,1483959293217]}}';
        $response = '{"result":[4100,3900],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $ret = $anki->getCardsFactors([1483959291685, 1483959293217]);
        $this->assertSame([
            1483959291685 => 4100,
            1483959293217 => 3900,
        ], $ret);
    }

    public function testGetCardFactor()
    {
        $request = '{"action":"getEaseFactors","version":6,"params":{"cards":[1483959291685]}}';
        $response = '{"result":[4100],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $ret = $anki->getCardFactor(1483959291685);
        $this->assertSame(4100, $ret);
    }

    public function testSetCardsFactors()
    {
        $request = '{"action":"setEaseFactors","version":6,"params":{"cards":[1483959291685,1483959293217],"easeFactors":[4100,3900]}}';
        $response = '{"result":[true,true],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->setCardsFactors([1483959291685, 1483959293217], [4100, 3900]));
    }

    public function testSetCardFactor()
    {
        $request = '{"action":"setEaseFactors","version":6,"params":{"cards":[1483959291685],"easeFactors":[4100]}}';
        $response = '{"result":[true],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->setCardFactor(1483959291685, 4100));
    }

    public function testSuspendCards()
    {
        $request = '{"action":"suspend","version":6,"params":{"cards":[1483959291685,1483959293217]}}';
        $response = '{"result":true,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->suspendCards([1483959291685, 1483959293217]));
    }

    public function testUnsuspendCards()
    {
        $request = '{"action":"unsuspend","version":6,"params":{"cards":[1483959291685,1483959293217]}}';
        $response = '{"result":true,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->unsuspendCards([1483959291685, 1483959293217]));
    }

    public function testAreCardsSuspended()
    {
        $request = '{"action":"areSuspended","version":6,"params":{"cards":[1483959291685,1483959293217]}}';
        $response = '{"result":[false,true],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $bools = $anki->areCardsSuspended([1483959291685, 1483959293217]);
        $this->assertContainsOnly('bool', $bools);
        $this->assertCount(2, $bools);
    }

    public function testIsCardSuspended()
    {
        $request = '{"action":"areSuspended","version":6,"params":{"cards":[1483959291685]}}';
        $response = '{"result":[false],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $bool = $anki->isCardSuspended(1483959291685);
        $this->assertFalse($bool);
    }

    public function testAreCardsDue()
    {
        $request = '{"action":"areDue","version":6,"params":{"cards":[1483959291685,1483959293217]}}';
        $response = '{"result":[false,true],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $bools = $anki->areCardsDue([1483959291685, 1483959293217]);
        $this->assertContainsOnly('bool', $bools);
        $this->assertCount(2, $bools);
    }

    public function testIsCardDue()
    {
        $request = '{"action":"areDue","version":6,"params":{"cards":[1483959291685]}}';
        $response = '{"result":[false],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $bool = $anki->isCardDue(1483959291685);
        $this->assertFalse($bool);
    }

    public function testGetCardsIntervals()
    {
        $request = '{"action":"getIntervals","version":6,"params":{"cards":[1502298033753,1502298036657],"complete":true}}';
        $response = '{"result":[[-120,-180,-240,-300,-360,-14400],[-120,-180,-240,-300,-360,-14400,1,3]],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $intervals = $anki->getCardsIntervals([1502298033753, 1502298036657]);
        $this->assertSame([
            1502298033753 => [120, 180, 240, 300, 360, 14400],
            1502298036657 => [120, 180, 240, 300, 360, 14400, 1 * 86400, 3 * 86400],
        ], $intervals);
    }

    public function testGetCardIntervals()
    {
        $request = '{"action":"getIntervals","version":6,"params":{"cards":[1502298033753],"complete":true}}';
        $response = '{"result":[[-120,-180,-240,-300,-360,-14400]],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $intervals = $anki->getCardIntervals(1502298033753);
        $this->assertSame([120, 180, 240, 300, 360, 14400], $intervals);
    }

    public function testFindCards()
    {
        $request = '{"action":"findCards","version":6,"params":{"query":"deck:current"}}';
        $response = '{"result":[1494723142483,1494703460437,1494703479525],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $cardIds = $anki->findCards('deck:current');
        $this->assertContainsOnly('int', $cardIds);
        $this->assertCount(3, $cardIds);
    }

    public function testGetCards()
    {
        $request = '{"action":"cardsInfo","version":6,"params":{"cards":[1498938915662,1502098034048]}}';
        $response = '{"result":[{"answer":"back content","question":"front content","deckName":"Default","modelName":"Basic","fieldOrder":1,"fields":{"Front":{"value":"front content","order":0},"Back":{"value":"back content","order":1}},"css":"p {font-family:Arial;}","cardId":1498938915662,"interval":16,"note":1502298033753,"ord":1,"type":0,"queue":0,"due":1,"reps":1,"lapses":0,"left":6},{"answer":"back content","question":"front content","deckName":"Default","modelName":"Basic","fieldOrder":0,"fields":{"Front":{"value":"front content","order":0},"Back":{"value":"back content","order":1}},"css":"p {font-family:Arial;}","cardId":1502098034048,"interval":23,"note":1502298033753,"ord":1,"type":0,"queue":0,"due":1,"reps":1,"lapses":0,"left":6}],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $cards = $anki->getCards([1498938915662, 1502098034048]);
        $this->assertContainsOnly('array', $cards);
        $this->assertCount(2, $cards);
    }

    public function testGetCardsExceptionWhenCardIdNotExist()
    {
        $request = '{"action":"cardsInfo","version":6,"params":{"cards":[123,1498938915662,1502098034048,456]}}';
        $response = '{"result":[{"answer":"back content","question":"front content","deckName":"Default","modelName":"Basic","fieldOrder":1,"fields":{"Front":{"value":"front content","order":0},"Back":{"value":"back content","order":1}},"css":"p {font-family:Arial;}","cardId":1498938915662,"interval":16,"note":1502298033753,"ord":1,"type":0,"queue":0,"due":1,"reps":1,"lapses":0,"left":6},{"answer":"back content","question":"front content","deckName":"Default","modelName":"Basic","fieldOrder":0,"fields":{"Front":{"value":"front content","order":0},"Back":{"value":"back content","order":1}},"css":"p {font-family:Arial;}","cardId":1502098034048,"interval":23,"note":1502298033753,"ord":1,"type":0,"queue":0,"due":1,"reps":1,"lapses":0,"left":6}],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->expectException(NotFoundException::class);
        $anki->getCards([123, 1498938915662, 1502098034048, 456]);
    }

    public function testGetCard()
    {
        $request = '{"action":"cardsInfo","version":6,"params":{"cards":[1498938915662]}}';
        $response = '{"result":[{"answer":"back content","question":"front content","deckName":"Default","modelName":"Basic","fieldOrder":1,"fields":{"Front":{"value":"front content","order":0},"Back":{"value":"back content","order":1}},"css":"p {font-family:Arial;}","cardId":1498938915662,"interval":16,"note":1502298033753,"ord":1,"type":0,"queue":0,"due":1,"reps":1,"lapses":0,"left":6}],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $card = $anki->getCard(1498938915662);
        $this->assertIsArray($card);
    }

    public function testGetReviewCountToday()
    {
        $request = '{"action":"getNumCardsReviewedToday","version":6}';
        $response = '{"result":0,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $count = $anki->getReviewCountToday();
        $this->assertSame(0, $count);
    }

    public function testGetReviewsOfDeck()
    {
        $request = '{"action":"cardReviews","version":6,"params":{"deck":"default","startID":1594194095740}}';
        $response = '{"result":[[1594194095746,1485369733217,-1,3,4,-60,2500,6157,0],[1594201393292,1485369902086,-1,1,-60,-60,0,4846,0]],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $reviews = $anki->getReviewsOfDeck('default', 1594194095740);
        $this->assertContainsOnlyInstancesOf(Review::class, $reviews);
        $this->assertCount(2, $reviews);
        $this->assertSame(1594194095746, $reviews[0]->getReviewTime());
        $this->assertSame(1485369733217, $reviews[0]->getCardId());
        $this->assertSame(3, $reviews[0]->getButtonPressed());
    }

    public function testGetLastReviewTimeOfDeck()
    {
        $request = '{"action":"getLatestReviewID","version":6,"params":{"deck":"default"}}';
        $response = '{"result":1594194095746,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $lastTimeWithMs = $anki->getLastReviewTimeOfDeck('default');
        $this->assertSame(1594194095746, $lastTimeWithMs);
    }

    public function testAddReviews()
    {
        $request = '{"action":"insertReviews","version":6,"params":{"reviews":[[1594194095746,1485369733217,-1,3,4,-60,2500,6157,0],[1594201393292,1485369902086,-1,1,-60,-60,0,4846,0]]}}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $reviews = [
            new Review(...[1594194095746, 1485369733217, -1, 3,   4, -60, 2500, 6157, 0]),
            new Review(...[1594201393292, 1485369902086, -1, 1, -60, -60,    0, 4846, 0]),
        ];
        $this->assertSame($anki, $anki->addReviews($reviews));
    }

    public function testGuiFindCards()
    {
        $request = '{"action":"guiBrowse","version":6,"params":{"query":"deck:current"}}';
        $response = '{"result":[1494723142483,1494703460437,1494703479525],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $cards = $anki->guiFindCards('deck:current');
        $this->assertSame([1494723142483, 1494703460437, 1494703479525], $cards);
    }

    public function testGuiAddNote()
    {
        $request = '{"action":"guiAddCards","version":6,"params":{"note":{"deckName":"Default","modelName":"Cloze","fields":{"Text":"The capital of Romania is {{c1::Bucharest}}","Extra":"Romania is a country in Europe"},"options":{"closeAfterAdding":true},"tags":["countries"]}}}';
        $response = '{"result":1496198395707,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $note = (new Note('Default', 'Cloze', [
            "Text" => "The capital of Romania is {{c1::Bucharest}}",
            "Extra" => "Romania is a country in Europe",
        ]))->setOption('closeAfterAdding', true)->setTags(['countries']);
        $noteId = $anki->guiAddNote($note);
        $this->assertSame(1496198395707, $noteId);
    }

    public function testGuiGetCurrentCard()
    {
        $request = '{"action":"guiCurrentCard","version":6}';
        $response = '{"result":{"answer":"back content","question":"front content","deckName":"Default","modelName":"Basic","fieldOrder":0,"fields":{"Front":{"value":"front content","order":0},"Back":{"value":"back content","order":1}},"template":"Forward","cardId":1498938915662,"buttons":[1,2,3],"nextReviews":["<1m","<10m","4d"]},"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $card = $anki->guiGetCurrentCard();
        $this->assertSame(1498938915662, $card['cardId']);
    }

    public function testGuiShowCurrentQuestion()
    {
        $request = '{"action":"guiShowQuestion","version":6}';
        $response = '{"result":true,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->guiShowCurrentQuestion());
    }

    public function testGuiShowCurrentAnswer()
    {
        $request = '{"action":"guiShowAnswer","version":6}';
        $response = '{"result":true,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->guiShowCurrentAnswer());
    }

    public function testGuiAnswerCurrentCard()
    {
        $request = '{"action":"guiAnswerCard","version":6,"params":{"ease":1}}';
        $response = '{"result":true,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->guiAnswerCurrentCard(1));
    }

    public function testGuiExitAnki()
    {
        $request = '{"action":"guiExitAnki","version":6}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->guiExitAnki());
    }

    public function testAddNote()
    {
        $request = '{"action":"addNote","version":6,"params":{"note":{"deckName":"Default","modelName":"Basic","fields":{"Front":"front content","Back":"back content"},"options":{"allowDuplicate":false,"duplicateScope":"deck"},"tags":["yomichan"],"audio":[{"url":"https://assets.languagepod101.com/dictionary/japanese/audiomp3.php?kanji=猫&kana=ねこ","filename":"yomichan_ねこ_猫.mp3","skipHash":"7e2c2f954ef6051373ba916f000168dc","fields":["Front"]}]}}}';
        $response = '{"result":1496198395707,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $audios = [[
            "url" => "https://assets.languagepod101.com/dictionary/japanese/audiomp3.php?kanji=猫&kana=ねこ",
            "filename" => "yomichan_ねこ_猫.mp3",
            "skipHash" => "7e2c2f954ef6051373ba916f000168dc",
            "fields" => ["Front"],
        ]];
        $note = (new Note('Default', 'Basic', [
            "Front" => "front content",
            "Back" => "back content",
        ]))->setTags(['yomichan'])
            ->setOption('allowDuplicate', false)
            ->setOption('duplicateScope', 'deck')
            ->setAudios($audios);
        $noteId = $anki->addNote($note);
        $this->assertSame(1496198395707, $noteId);
    }

    public function testUpdateNote()
    {
        $request = '{"action":"updateNoteFields","version":6,"params":{"note":{"id":1514547547030,"fields":{"Front":"new front content","Back":"new back content"},"audio":[{"url":"https://assets.languagepod101.com/dictionary/japanese/audiomp3.php?kanji=猫&kana=ねこ","filename":"yomichan_ねこ_猫.mp3","skipHash":"7e2c2f954ef6051373ba916f000168dc","fields":["Front"]}]}}}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $noteId = 1514547547030;
        $fields = [
            "Front" => "new front content",
            "Back" => "new back content",
        ];
        $audios = [[
            "url" => "https://assets.languagepod101.com/dictionary/japanese/audiomp3.php?kanji=猫&kana=ねこ",
            "filename" => "yomichan_ねこ_猫.mp3",
            "skipHash" => "7e2c2f954ef6051373ba916f000168dc",
            "fields" => ["Front"],
        ]];
        $this->assertSame($anki, $anki->updateNote($noteId, $fields, $audios));
    }

    public function testTagNotes()
    {
        $request = '{"action":"addTags","version":6,"params":{"notes":[1483959289817,1483959291695],"tags":"european-languages"}}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->tagNotes('european-languages', [1483959289817, 1483959291695]));
    }

    public function testUntagNotes()
    {
        $request = '{"action":"removeTags","version":6,"params":{"notes":[1483959289817,1483959291695],"tags":"european-languages"}}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->untagNotes('european-languages', [1483959289817, 1483959291695]));
    }

    public function testGetAllTags()
    {
        $request = '{"action":"getTags","version":6}';
        $response = '{"result":["european-languages","idioms"],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $tagNames = $anki->getAllTags();
        $this->assertSame(["european-languages", "idioms"], $tagNames);
    }

    public function testFindNotes()
    {
        $request = '{"action":"findNotes","version":6,"params":{"query":"deck:current"}}';
        $response = '{"result":[1483959289817,1483959291695],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $noteIds = $anki->findNotes('deck:current');
        $this->assertSame([1483959289817, 1483959291695], $noteIds);
    }

    public function testGetNotes()
    {
        $request = '{"action":"notesInfo","version":6,"params":{"notes":[1502298033753]}}';
        $response = '{"result":[{"noteId":1502298033753,"modelName":"Basic","tags":["tag","another_tag"],"fields":{"Front":{"value":"front content","order":0},"Back":{"value":"back content","order":1}}}],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $notes = $anki->getNotes([1502298033753]);
        $this->assertCount(1, $notes);
        $this->assertSame(1502298033753, $notes[1502298033753]['noteId']);
    }

    public function testGetNotesExceptionWhenNoteIdNotExist()
    {
        $request = '{"action":"notesInfo","version":6,"params":{"notes":[123,1502298033753,456]}}';
        $response = '{"result":[{"noteId":1502298033753,"modelName":"Basic","tags":["tag","another_tag"],"fields":{"Front":{"value":"front content","order":0},"Back":{"value":"back content","order":1}}}],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->expectException(NotFoundException::class);
        $anki->getNotes([123, 1502298033753, 456]);
    }

    public function testGetNote()
    {
        $request = '{"action":"notesInfo","version":6,"params":{"notes":[1502298033753]}}';
        $response = '{"result":[{"noteId":1502298033753,"modelName":"Basic","tags":["tag","another_tag"],"fields":{"Front":{"value":"front content","order":0},"Back":{"value":"back content","order":1}}}],"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $note = $anki->getNote(1502298033753);
        $this->assertSame(1502298033753, $note['noteId']);
    }

    public function testDeleteNotes()
    {
        $request = '{"action":"deleteNotes","version":6,"params":{"notes":[1502298033753]}}';
        $response = '{"result":null,"error":null}';
        $anki = $this->getMockedAnki($this->getMockedClient($response, $request));

        $this->assertSame($anki, $anki->deleteNotes([1502298033753]));
    }
}
