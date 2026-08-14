function PriorityAttention({
    alerts
}) {


    return (

        <div className="rounded-xl bg-white p-6 shadow">


            <h2 className="text-xl font-bold text-slate-800">

                ⚠ Priority Attention

            </h2>




            {
                alerts.length === 0

                ?

                (

                    <p className="mt-4 text-slate-500">

                        No critical cases detected.

                    </p>

                )


                :


                (

                <div className="mt-5 space-y-4">


                {
                    alerts.map((item)=>(
                        

                        <div

                        key={item.resident_id}

                        className="rounded-lg border border-red-200 bg-red-50 p-4"

                        >


                            <div className="flex justify-between">


                                <div>


                                    <h3 className="font-bold text-slate-800">

                                        {item.resident_name}

                                    </h3>


                                    <p className="mt-2 text-sm text-slate-600">

                                        {item.recommendation}

                                    </p>


                                </div>





                                <span className="h-fit rounded-full bg-red-600 px-3 py-1 text-sm font-bold text-white">

                                    {item.priority}

                                </span>



                            </div>


                        </div>


                    ))
                }


                </div>

                )


            }



        </div>

    );


}


export default PriorityAttention;