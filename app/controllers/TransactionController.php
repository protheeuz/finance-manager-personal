<?php


use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;

/**
 * Class TransactionController
 */
class TransactionController extends BaseController
{

    protected $_repository;

    /**
     * @param TJRI $repository
     */
    public function __construct(TJRI $repository)
    {
        $this->_repository = $repository;
        View::share('menu', 'home');
    }

    /**
     * @param string $what
     *
     * @return \Illuminate\View\View
     */
    public function create($what = 'deposit')
    {
        // get accounts with names and id's.
        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accountRepository */
        $accountRepository = App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts = $accountRepository->getActiveDefaultAsSelectList();

        // get budgets as a select list.
        /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets = $budgetRepository->getAsSelectList();
        $budgets[0] = '(no budget)';


        return View::make('transactions.create')->with('accounts', $accounts)->with('budgets', $budgets)->with(
            'what', $what
        );
    }

    public function delete(TransactionJournal $transactionJournal)
    {
        return View::make('transactions.delete')->with('journal', $transactionJournal);


    }

    public function destroy(TransactionJournal $transactionJournal)
    {
        $transactionJournal->delete();

        return Redirect::route('transactions.index');

    }

    /**
     * @param $journalId
     *
     * @return $this|\Illuminate\View\View
     */
    public function edit(TransactionJournal $journal)
    {
        // type is useful for display:
        $what = strtolower($journal->transactiontype->type);

        // some lists prefilled:
        // get accounts with names and id's.
        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accountRepository */
        $accountRepository = App::make('Firefly\Storage\Account\AccountRepositoryInterface');
        $accounts = $accountRepository->getActiveDefaultAsSelectList();

        // get budgets as a select list.
        /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');
        $budgets = $budgetRepository->getAsSelectList();
        $budgets[0] = '(no budget)';

        // data to properly display form:
        $data = [
            'date'      => $journal->date->format('Y-m-d'),
            'category'  => '',
            'budget_id' => 0
        ];
        $category = $journal->categories()->first();
        if (!is_null($category)) {
            $data['category'] = $category->name;
        }
        switch ($journal->transactiontype->type) {
            case 'Withdrawal':
                $data['account_id'] = $journal->transactions[0]->account->id;
                $data['beneficiary'] = $journal->transactions[1]->account->name;
                $data['amount'] = floatval($journal->transactions[1]->amount);
                $budget = $journal->budgets()->first();
                if (!is_null($budget)) {
                    $data['budget_id'] = $budget->id;
                }
                break;
            case 'Deposit':
                $data['account_id'] = $journal->transactions[1]->account->id;
                $data['beneficiary'] = $journal->transactions[0]->account->name;
                $data['amount'] = floatval($journal->transactions[1]->amount);
                break;
            case 'Transfer':
                $data['account_from_id'] = $journal->transactions[1]->account->id;
                $data['account_to_id'] = $journal->transactions[0]->account->id;
                $data['amount'] = floatval($journal->transactions[1]->amount);
                break;
        }

        return View::make('transactions.edit')->with('journal', $journal)->with('accounts', $accounts)->with(
            'what', $what
        )->with('budgets', $budgets)->with('data', $data);
    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function index()
    {
        $journals = $this->_repository->paginate(25);

        return View::make('transactions.index')->with('journals', $journals);
    }

    /**
     * @param $journalId
     *
     * @return $this|\Illuminate\View\View
     */
    public function show($journalId)
    {
//        $journal = $this->_journal->find($journalId);
//        if ($journal) {
//            return View::make('transactions.show')->with('journal', $journal);
//        }
//
//        return View::make('error')->with('message', 'Invalid journal');
    }

    /**
     * @param $what
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store($what)
    {
        $transactionJournal = $this->_repository->store($what, Input::all());
        if ($transactionJournal->id) {
            Session::flash('success', 'Transaction "' . $transactionJournal->description . '" saved!');
            if (Input::get('create') == '1') {
                return Redirect::route('transactions.create', [$what])->withInput();
            } else {
                return Redirect::route('transactions.index');
            }
        } else {
            Session::flash('error', 'Could not save transaction: ' . $transactionJournal->errors()->first());

            return Redirect::route('transactions.create', [$what])->withInput()->withErrors(
                $transactionJournal->errors()
            );
        }


    }

    public function update(TransactionJournal $journal)
    {
        $journal = $this->_repository->update($journal, Input::all());
        if ($journal->validate()) {
            // has been saved, return to index:
            Session::flash('success', 'Transaction updated!');

            return Redirect::route('transactions.index');
        } else {
            Session::flash('error', 'Could not update transaction: ' . $journal->errors()->first());

            return Redirect::route('transactions.edit', $journal->id)->withInput()->withErrors($journal->errors());
        }


    }

} 