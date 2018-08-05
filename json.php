<script>
var t1=[
  {
    'name': 'afro',
    'designation': 'dvlpr'
  },
  {
    'name': 'afro1',
    'designation': 'dvlpr1'
  }
];

var t2=[
  {
    'name': 'afro',
    'designation': 'dvlpr'
  },
  {
    'name': 'afro1',
    'designation': 'dvlpr1'
  }
];

var t31=[
  {
    'name': 'afro',
    'designation': 'dvlpr'
  },
  {
    'name': 'afro1',
    'designation': 'dvlpr1'
  }
];

var t32=[
  {
    'name': 'afro',
    'designation': 'dvlpr'
  },
  {
    'name': 'afro1',
    'designation': 'dvlpr1'
  }
];

var t3=[];

if(t31.length>0&&t32.length>0){
	t3={'t31':t31,'t32':t32};
}else if(t31.length>0){
	t3={'t31':t31};
}else{
	t3={'t32':t32};
}

/*var a=JSON.stringify(t1);
var b=JSON.stringify(t2);
var c=JSON.stringify(t31);
var d=JSON.stringify(t32);*/
var finalObj = {myfirst:t1,mysecond:t2,mythird:t3};

console.log(JSON.stringify(finalObj));
</script>