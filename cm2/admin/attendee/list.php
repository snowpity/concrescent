<?php

use App\Database\Model\PaymentStatus;

require_once __DIR__ .'/../../lib/database/attendee.php';
require_once __DIR__ .'/../../lib/database/forms.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('attendees', array('||', 'attendees', 'attendees-view', 'attendees-edit', 'attendees-delete'));
$can_view = $adb->user_has_permission($admin_user, 'attendees-view');
$can_edit = $adb->user_has_permission($admin_user, 'attendees-edit');
$can_delete = $adb->user_has_permission($admin_user, 'attendees-delete');

$atdb = new cm_attendee_db($db);
$name_map = $atdb->get_badge_type_name_map();

$columns = array_merge(
	[
		[
			'name' => 'ID',
			'key' => 'id-string',
			'type' => 'text'
        ],
		[
			'name' => 'Badge Type',
			'key' => 'badge-type-name',
			'type' => 'text'
        ],
		[
			'name' => 'Email Address',
			'key' => 'email-address',
			'type' => 'email-subbed'
        ],
    ],
	[
		[
			'name' => 'Payment Status',
			'key' => 'payment-status',
			'type' => 'status-label'
        ],
		[
			'name' => 'Payment Date',
			'key' => 'payment-date',
			'type' => 'text'
        ],
        [
            'name' => 'P',
            'key' => 'print-count',
            'type' => 'numeric'
        ],
        [
            'name' => 'C',
            'key' => 'checkin-count',
            'type' => 'numeric'
        ],
    ]
);

enum Type {
    case Select;
    case SelectMultiple;
    case Checkbox;
}
class Entry {
    public function __construct(
        public string $label,
        public string $value,
        public mixed $meaning = null {
            get => $this->meaning ?: $this->value;
        },
        public bool $checked = false,
        public string $metaClass = "",
    ) { }
}
class Filter {
    public function __construct(
        public Type $type,
        public string $label,
        public string $name,
        /** @var Entry[] */
        public array $entries,
    ) { }

    public function isActive(): bool
    {
        foreach ($this->entries as $entry) {
            if ($entry->checked) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Entry[]
     */
    public function getCheckedEntries(): array
    {
        return array_filter(
            $this->entries,
            static fn ($entry) => $entry->checked,
        );
    }

    public function checkEntry(string $value): bool
    {
        foreach ($this->entries as $entry) {
            if ($entry->value === $value) {
                $entry->checked = true;
                return true;
            }
        }

        return false;
    }
}

$paymentStatusFilter = new Filter(
    Type::Checkbox,
    'Payment status',
    'pay',
    [
        new Entry(
            label: 'Completed',
            value: 'comp',
            meaning: PaymentStatus::Completed,
            metaClass: 'cm-status-label cm-status-completed'),
        new Entry('Incomplete',
            value: 'incomp',
            meaning: PaymentStatus::Incomplete,
            metaClass: 'cm-status-label cm-status-incomplete'),
        new Entry('Rejected',
            value: 'reject',
            meaning: PaymentStatus::Rejected,
            metaClass: 'cm-status-label cm-status-rejected'),
        new Entry('Cancelled',
            value: 'cancel',
            meaning: PaymentStatus::Cancelled,
            metaClass: 'cm-status-label cm-status-cancelled'),
        new Entry('Refunded',
            value: 'refund',
            meaning: PaymentStatus::Refunded,
            metaClass: 'cm-status-label cm-status-refunded'),
    ]
);

$filters[] = $paymentStatusFilter;

$activefilters = [];
foreach ($filters as $filter) {
    if (array_key_exists($filter->name, $_GET)) {
        $value = $_GET[$filter->name];
        if (is_array($value)) {
            foreach($value as $entry) {
                $filter->checkEntry($entry);
            }
        } elseif (is_string($value)) {
            $filter->checkEntry($value);
        }
    }

    if ($filter->isActive()) {
        $activefilters[$filter->name] = $filter->getCheckedEntries();
    }
}

$filterWithPaymentStatus = $activefilters[$paymentStatusFilter->name] ?? null
    ? array_map(static fn ($filter) => $filter->meaning, $activefilters[$paymentStatusFilter->name])
    : null;

$attendees = $atdb->shortlist_attendees(
    name_map: $name_map,
    paymentStatus: $filterWithPaymentStatus,
);

$pageContent = [
    'columns' => $columns,
    'searchCriterias' => 'name, badge type, contact info, or transaction ID',
    'searchQuery' => $_POST['sq'] ?? '',
    'editView' => ($can_edit ? 'Edit' : 'View'),
    'rowAction' => [
        (($can_view || $can_edit) ? 'edit' : null),
        ($can_delete ? 'delete' : null)
    ],
    'tableAction' => [($can_edit ? 'add' : null)],
    'filters' => $filters,
    'attendees' => $attendees,
];

global $twig;
$requestHeaders = getallheaders();
$isHtmxFragmentRequest = ($requestHeaders['Hx-Request'] ?? false) === 'true';
if ($isHtmxFragmentRequest) {
    echo $twig->render('pages/admin/generic/list.fragment.twig', [
        ...$pageContent
    ]);
    exit(0);
}

echo $twig->render('pages/admin/generic/list.twig', [
    'title' => 'Attendees',
    'page_id' => 'attendees',
    ...$pageContent
]);
