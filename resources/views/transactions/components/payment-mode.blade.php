@if ($row->payments_mode === 'Manual') 
<span class="badge bg-light-warning fs-7">{{$row->payments_mode}}</span>
@elseif($row->payments_mode === 'Stripe') 
<span class="badge bg-light-success fs-7">{{$row->payments_mode}}</span>
@elseif($row->payments_mode === 'Paypal')
<span class="badge bg-light-primary fs-7">{{$row->payments_mode}}</span>
@elseif($row->payments_mode === 'Cash')
<span class="badge bg-light-info fs-7">{{$row->payments_mode}}</span>
@elseif($row->payments_mode === 'Razorpay')
<span class="badge bg-light-danger fs-7">{{$row->payments_mode}}</span>
@endif
