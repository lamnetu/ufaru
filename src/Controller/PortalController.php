<?php

declare(strict_types=1);

namespace App\Controller;

use App\Content\PortalContent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PortalController extends AbstractController
{
    public function __construct(private readonly PortalContent $content) {}

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('portal/home.html.twig', [
            'services'      => $this->content->services(),
            'alerts'        => $this->content->alerts(),
            'stats'         => $this->content->stats(),
            'news'          => $this->content->news(),
            'announcements' => $this->content->announcements(),
            'documents'     => $this->content->documents(),
            'ads'           => $this->content->ads(),
            'adCategories'  => $this->content->adCategories(),
            'districts'     => $this->content->districts(),
            'topics'        => $this->content->appealTopics(),
        ]);
    }

    #[Route('/novosti', name: 'app_news', methods: ['GET'])]
    public function news(Request $request): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $cat = (string) $request->query->get('cat', 'all');
        $items = $this->content->news();

        if ('' !== $q) {
            $needle = mb_strtolower($q);
            $items = array_values(array_filter($items, static fn (array $n): bool =>
            str_contains(mb_strtolower($n['title'].' '.$n['excerpt']), $needle)));
        }
        if ('all' !== $cat) {
            $items = array_values(array_filter($items, static fn (array $n): bool => $n['category'] === $cat));
        }

        return $this->render('portal/news.html.twig', [
            'items' => $items,
            'q' => $q,
            'cat' => $cat,
            'categories' => array_values(array_unique(array_column($this->content->news(), 'category'))),
            'total' => \count($this->content->news()),
        ]);
    }

    #[Route('/novosti/{slug}', name: 'app_article', methods: ['GET'])]
    public function article(string $slug): Response
    {
        $item = $this->content->findNews($slug);
        if (null === $item) {
            throw $this->createNotFoundException('Новость не найдена');
        }

        $related = array_values(array_filter(
            $this->content->news(),
            static fn (array $n): bool => $n['slug'] !== $slug
        ));

        return $this->render('portal/article.html.twig', [
            'item' => $item,
            'related' => \array_slice($related, 0, 3),
        ]);
    }

    #[Route('/obrashchenie', name: 'app_appeal', methods: ['POST'])]
    public function appeal(Request $request): Response
    {
        $back = $this->generateUrl('app_home').'#appeal';

        if (!$this->isCsrfTokenValid('appeal', (string) $request->request->get('_token'))) {
            $this->addFlash('appeal_errors', ['Сессия истекла. Отправьте форму ещё раз.']);
            return $this->redirect($back);
        }

        $data = [
            'name'    => trim((string) $request->request->get('name')),
            'email'   => trim((string) $request->request->get('email')),
            'topic'   => (string) $request->request->get('topic'),
            'message' => trim((string) $request->request->get('message')),
        ];

        $errors = [];
        if (mb_strlen($data['name']) < 2)                                  $errors['name'] = 'Укажите имя и фамилию';
        if (!filter_var($data['email'], \FILTER_VALIDATE_EMAIL))           $errors['email'] = 'Проверьте адрес почты';
        if (!\in_array($data['topic'], $this->content->appealTopics(), true)) $errors['topic'] = 'Выберите тему обращения';
        if (mb_strlen($data['message']) < 20)                              $errors['message'] = 'Минимум 20 символов';
        if (mb_strlen($data['message']) > 2000)                            $errors['message'] = 'Не более 2000 символов';

        $attachment = $request->files->get('attachment');
        $attachmentName = null;
        if ($attachment) {
            if (!$attachment->isValid()) {
                $errors['attachment'] = 'Файл не загрузился, попробуйте другой';
            } elseif ($attachment->getSize() > 10 * 1024 * 1024) {
                $errors['attachment'] = 'Файл больше 10 МБ';
            } elseif (!\in_array(strtolower((string) $attachment->getClientOriginalExtension()), ['pdf','jpg','jpeg','png','doc','docx','xls','xlsx'], true)) {
                $errors['attachment'] = 'Допустимы PDF, JPG, PNG, DOC, XLS';
            } else {
                $attachmentName = $attachment->getClientOriginalName();
            }
        }

        if ($errors) {
            $this->addFlash('appeal_errors', $errors);
            $this->addFlash('appeal_old', $data);
            return $this->redirect($back);
        }

        $number = sprintf('ОБ-%s-%04d', (new \DateTimeImmutable())->format('ymd'), random_int(1000, 9999));
        $record = [
            'number' => $number,
            'topic' => $data['topic'],
            'message' => $data['message'],
            'email' => $data['email'],
            'attachment' => $attachmentName,
            'created' => new \DateTimeImmutable(),
            'status' => 'Зарегистрировано',
        ];

        $session = $request->getSession();
        $list = $session->get('appeals', []);
        array_unshift($list, $record);
        $session->set('appeals', \array_slice($list, 0, 20));

        $this->addFlash('appeal_ok', $record);
        return $this->redirect($back);
    }

    #[Route('/lk', name: 'app_cabinet', methods: ['GET'])]
    public function cabinet(Request $request): Response
    {
        return $this->render('portal/cabinet.html.twig', [
            'appeals' => $request->getSession()->get('appeals', []),
        ]);
    }
}
