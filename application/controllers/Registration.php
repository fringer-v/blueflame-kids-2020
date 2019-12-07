<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH.'core/BF_Controller.php');
include_once(APPPATH.'helpers/output_helper.php');

define('MAX_REG_TAB', 6);

class Registration extends BF_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	private function link($target, $reg_tab, $i)
	{
		$attr = [ 'class'=>'menu-item', 'onclick'=>'window.location=\'?reg_tab='.$i.'\';' ];
		if ($reg_tab == $i)
			$attr['selected'] = null;
		return $attr;
	}

	public function index()
	{
		$this->authorize();

		$this->header('iPad Registrierung', false);
		
		$reg_tab = in('reg_tab', 1);
		$reg_tab_v = $reg_tab->getValue();

		$participant_row = $this->get_participant_row(0);

		$register_participant = new Form('register_participant', 'registration', 2, array('class'=>'input-table'));
		$prt_id = $register_participant->addHidden('prt_id');
		$prt_id->persistent();
	
		$prt_firstname = textinput('prt_firstname', $participant_row['prt_firstname'],
			[ 'class'=>'ipad-input', 'placeholder'=>'Vorname', 'style'=>'width: 175px;' ]);
		$prt_firstname->setRule('required');
		$prt_lastname = textinput('prt_lastname', $participant_row['prt_lastname'],
			[ 'class'=>'ipad-input', 'placeholder'=>'Nachname', 'style'=>'width: 260px;' ]);
		$prt_lastname->setRule('required');
		$prt_birthday = new NumericField('prt_birthday', $participant_row['prt_birthday'],
			[ 'class'=>'ipad-input', 'placeholder'=>'DD.MM.JJJJ', 'style'=>'width: 160px; font-family: Monospace;' ]);
		$prt_birthday->setRule('is_valid_date');

		$prt_supervision_firstname = textinput('prt_supervision_firstname', $participant_row['prt_supervision_firstname'],
			[ 'class'=>'ipad-input', 'placeholder'=>'Vorname', 'style'=>'width: 175px;' ]);
		$prt_supervision_lastname = textinput('prt_supervision_lastname', $participant_row['prt_supervision_lastname'],
			[ 'class'=>'ipad-input', 'placeholder'=>'Nachname', 'style'=>'width: 260px;' ]);
		$prt_supervision_cellphone = new NumericField('prt_supervision_cellphone', $participant_row['prt_supervision_cellphone'],
			[ 'class'=>'ipad-input', 'style'=>'width: 260px; font-family: Monospace;' ]);
		$register_participant->addSpace();

		div(array('class'=>'topnav'));
		table();
		tr();
		td([ 'colspan'=>MAX_REG_TAB+1, 'style'=>'text-align: left;' ]);
		a([ 'href'=>'participant' ], img([ 'src'=>base_url('/img/bf-kids-logo2.png'), 'style'=>'height: 40px; width: auto; position: relative; bottom: -2px;']));
		_td();
		_tr();
		tr();
		td([ 'style'=>'width: 3px; padding: 0;' ], nbsp());
		for ($i=1; $i<=MAX_REG_TAB; $i++) {
			td([ 'style'=>'height: 22px; padding: 0px 2px 5px 2px;' ], div([ 'class'=>'green-box', 'style'=>'width: 100%;' ], 'Angemeldet'));
			td([ 'style'=>'width: 3px; padding: 0;' ], nbsp());
		}
		_tr();
		_tr();
		tr([ 'style'=>'border-bottom: 1px solid black; padding: 8px 16px;' ]);
		td([ 'style'=>'width: 3px; padding: 0;' ], nbsp());
		for ($i=1; $i<=MAX_REG_TAB; $i++) {
			td($this->link('participant', $reg_tab_v, $i), 'Kind '.$i);
			td([ 'style'=>'width: 3px; padding: 0;' ], nbsp());
		}
		_tr();
		_table();
		_div();

		$register_participant->open();

		table([ 'class'=>'ipad-table', 'style'=>'padding-top: 2px;' ]);
		tr();
		td(b('Kind:'));
		td(b('Begleitperson:'));
		_tr();
		tr();
		td();
			table([ 'style'=>'border: 1px solid black;' ]);
			tr();
			td($prt_firstname->html());
			td($prt_lastname->html());
			_tr();
			tr();
			td([ 'style'=>'text-align: right;' ], 'Geburtstag:');
			td($prt_birthday->html());
			_tr();
			_table();
		_td();
		td();
			table([ 'style'=>'border: 1px solid black;' ]);
			tr();
			td($prt_supervision_firstname->html());
			td($prt_supervision_lastname->html());
			_tr();
			tr();
			td([ 'style'=>'text-align: right;' ], 'Handy-Nr:');
			td($prt_supervision_cellphone->html());
			_tr();
			_table();
		_td();
		_tr();
		_table();

		$register_participant->close();

		script();
		out('
			function birthday_changed() {
				var value = $("#prt_birthday").val();
				const key = event.key;
				var new_value = checkDate(value, key === "Backspace" || key === "Delete");
				if (value != new_value)
					$("#prt_birthday").val(new_value);
			}
			$("#prt_birthday").keyup(birthday_changed);
		');
		_script();
		$this->footer();
	}
}
