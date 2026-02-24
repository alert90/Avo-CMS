<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="py-3 pr-3 h-100">
            <form method="post" action="{{route('pro.buy')}}" class=" h-100 d-flex flex-column "> @csrf
                <h5 class="mb-3">Upgrade to PRO to unlock unlimited access to all of our features, including:</h5>
                <button class="btn btn-info btn-block btn-md mb-3">
                    <img width="32px" class="mr-3" src="{{asset('/images/premium.png')}}" alt="Upgrade">
                    <strong>{{__("Upgrade for :price",['price'=>'$'.config('pro.price_yearly')])}}</strong>
                </button>
                <p class="text-center">
                    <i>* After purchasing, you can download the PRO version</i>
                </p>
            </form>
        </div>
    </div>
</div>
