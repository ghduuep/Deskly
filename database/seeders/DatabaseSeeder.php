<?php

namespace Database\Seeders;

use App\Enum\ChannelType;
use App\Enum\MessageDirection;
use App\Enum\MessageType;
use App\Enum\TicketPriority;
use App\Enum\TicketStatus;
use App\Enum\UserRole;
use App\Models\Channel;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with demo data.
     *
     * Every demo user signs in with the password "password".
     */
    public function run(): void
    {
        $admin = $this->createUserWithContact('Admin', 'admin@deskly.test', UserRole::Admin);
        $agent = $this->createUserWithContact('Ana Agente', 'agent@deskly.test', UserRole::Agent);
        $customer = $this->createUserWithContact('Carlos Cliente', 'customer@deskly.test', UserRole::Customer);

        $support = Group::factory()->create(['name' => 'Suporte', 'description' => 'Atendimento geral']);
        $support->users()->attach([$admin->id, $agent->id]);

        $channels = collect(ChannelType::cases())->mapWithKeys(fn (ChannelType $type) => [
            $type->value => Channel::factory()->create(['name' => ucfirst($type->value), 'type' => $type]),
        ]);

        $company = Company::factory()->create();

        $requesters = collect([$customer->contact])->merge(
            Contact::factory()->count(5)->create(['user_id' => null, 'company_id' => $company->id]),
        );

        $subjects = [
            'Não consigo acessar minha conta',
            'Cobrança duplicada no cartão',
            'Como exportar relatórios?',
            'Pedido chegou com defeito',
            'Integração com WhatsApp parou',
            'Dúvida sobre o plano anual',
            'Erro ao anexar arquivo',
            'Solicitação de reembolso',
        ];

        foreach ($subjects as $index => $title) {
            $requester = $requesters[$index % $requesters->count()];
            $channel = $channels->values()[$index % $channels->count()];
            $status = [TicketStatus::Open, TicketStatus::Pending, TicketStatus::Open, TicketStatus::Resolved][$index % 4];

            $ticket = Ticket::factory()->create([
                'contact_id' => $requester->id,
                'company_id' => $requester->company_id,
                'group_id' => $support->id,
                'assigned_id' => $index % 3 === 0 ? null : $agent->id,
                'title' => $title,
                'status' => $status,
                'priority' => TicketPriority::cases()[$index % 4],
            ]);

            Message::factory()->create([
                'ticket_id' => $ticket->id,
                'contact_id' => $requester->id,
                'channel_id' => $channel->id,
                'content' => "Olá, preciso de ajuda: {$title}.",
                'direction' => MessageDirection::Inbound,
            ]);

            if ($status !== TicketStatus::Open) {
                Message::factory()->create([
                    'ticket_id' => $ticket->id,
                    'contact_id' => $agent->contact->id,
                    'channel_id' => $channel->id,
                    'content' => 'Oi! Já estou verificando para você.',
                    'direction' => MessageDirection::Outbound,
                ]);

                Message::factory()->create([
                    'ticket_id' => $ticket->id,
                    'contact_id' => $agent->contact->id,
                    'content' => 'Verificar histórico de pagamentos antes de responder.',
                    'type' => MessageType::Internal,
                    'direction' => null,
                ]);
            }
        }
    }

    private function createUserWithContact(string $name, string $email, UserRole $role): User
    {
        $user = User::factory()->create(['name' => $name, 'email' => $email, 'role' => $role]);

        Contact::factory()->create(['user_id' => $user->id, 'name' => $name, 'company_id' => null]);

        return $user->load('contact');
    }
}
