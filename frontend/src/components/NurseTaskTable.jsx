import { useState } from "react";


import {

    acceptNurseTask,

    completeNurseTask

} from "../api/nurseTaskApi";





function NurseTaskTable({

    tasks,

    onRefresh

}) {



    const [processingId,setProcessingId] =
        useState(null);







    const handleAccept = async(id)=>{


        try{


            setProcessingId(id);


            await acceptNurseTask(id);


            await onRefresh();


        }

        catch(error){

            console.error(error);

        }

        finally{

            setProcessingId(null);

        }


    };







    const handleComplete = async(id)=>{


        try{


            setProcessingId(id);


            await completeNurseTask(id);


            await onRefresh();


        }

        catch(error){

            console.error(error);

        }

        finally{

            setProcessingId(null);

        }


    };








return(


<div className="rounded-xl bg-white p-6 shadow">


<h2 className="text-xl font-bold text-slate-800">

🩺 Nurse Tasks

</h2>




{
tasks.length === 0

?

<p className="mt-5 text-slate-500">

No pending nurse tasks.

</p>


:


<div className="mt-5 overflow-x-auto">


<table className="w-full">


<thead>

<tr className="border-b text-left text-sm text-slate-500">


<th className="p-3">
Resident
</th>


<th className="p-3">
Task
</th>


<th className="p-3">
Status
</th>


<th className="p-3">
Assigned To
</th>


<th className="p-3">
Actions
</th>


</tr>

</thead>





<tbody>


{
tasks.map(task=>(


<tr
key={task.id}
className="border-b hover:bg-slate-50"
>


<td className="p-3 font-semibold">

{
task.resident?.full_name ??
"Unknown"
}

</td>




<td className="p-3">

{task.task_name}

</td>





<td className="p-3">


<span className="rounded-full bg-yellow-100 px-3 py-1 text-xs font-bold text-yellow-700">

{task.status}

</span>


</td>






<td className="p-3">


{
task.assigned_user?.full_name
??
"Not Assigned"
}


</td>






<td className="p-3">


<div className="flex gap-2">



<button

onClick={()=>
handleAccept(task.id)
}

disabled={
processingId===task.id
}

className="rounded-lg bg-blue-600 px-3 py-2 text-sm text-white"

>

Accept

</button>





<button

onClick={()=>
handleComplete(task.id)
}

disabled={
processingId===task.id
}

className="rounded-lg bg-green-600 px-3 py-2 text-sm text-white"

>

Complete

</button>



</div>


</td>



</tr>


))

}


</tbody>



</table>


</div>


}



</div>


);


}


export default NurseTaskTable;