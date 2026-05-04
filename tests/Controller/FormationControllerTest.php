<?php

namespace App\Tests\Controller;

use App\Entity\Formation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FormationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    /** @var EntityRepository<Formation> */
    private EntityRepository $formationRepository;
    private string $path = '/formation/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->formationRepository = $this->manager->getRepository(Formation::class);

        foreach ($this->formationRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Formation index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'formation[id]' => 'Testing',
            'formation[title]' => 'Testing',
            'formation[description]' => 'Testing',
            'formation[video_url]' => 'Testing',
            'formation[category]' => 'Testing',
            'formation[coach_id]' => 'Testing',
        ]);

        self::assertResponseRedirects('/formation');

        self::assertSame(1, $this->formationRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Formation();
        $fixture->setId('My Title');
        $fixture->setTitle('My Title');
        $fixture->setDescription('My Title');
        $fixture->setVideoUrl('My Title');
        $fixture->setCategory('My Title');
        $fixture->setCoachId('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Formation');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Formation();
        $fixture->setId('Value');
        $fixture->setTitle('Value');
        $fixture->setDescription('Value');
        $fixture->setVideoUrl('Value');
        $fixture->setCategory('Value');
        $fixture->setCoachId('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'formation[id]' => 'Something New',
            'formation[title]' => 'Something New',
            'formation[description]' => 'Something New',
            'formation[video_url]' => 'Something New',
            'formation[category]' => 'Something New',
            'formation[coach_id]' => 'Something New',
        ]);

        self::assertResponseRedirects('/formation');

        $fixture = $this->formationRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getId());
        self::assertSame('Something New', $fixture[0]->getTitle());
        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getVideoUrl());
        self::assertSame('Something New', $fixture[0]->getCategory());
        self::assertSame('Something New', $fixture[0]->getCoachId());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Formation();
        $fixture->setId('Value');
        $fixture->setTitle('Value');
        $fixture->setDescription('Value');
        $fixture->setVideoUrl('Value');
        $fixture->setCategory('Value');
        $fixture->setCoachId('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/formation');
        self::assertSame(0, $this->formationRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
