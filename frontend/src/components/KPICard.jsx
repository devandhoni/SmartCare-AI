function KPICard({
    title,
    value,
    icon,
    color
}) {


    return (

        <div className="rounded-xl bg-white p-6 shadow hover:shadow-lg transition">


            <div className="flex items-center justify-between">


                <div>

                    <p className="text-sm text-slate-500">
                        {title}
                    </p>


                    <h2 className="mt-3 text-4xl font-bold text-slate-800">

                        {value}

                    </h2>


                </div>



                <div
                className={`text-4xl ${color}`}
                >

                    {icon}

                </div>


            </div>


        </div>

    );


}


export default KPICard;