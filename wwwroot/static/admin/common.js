function showCode(img){
    if(!layui.jquery('#'+img)){return;}
    layui.jquery('#'+img).attr('src','index/code.html?'+Math.random());
}
//自适应弹框
function reSizeFrame() {
    if (typeof parent === 'object'){
        let index = parent.layer.getFrameIndex(window.name);
        if (typeof index !== "undefined"){
            parent.layer.iframeAuto(index);
        }
    }
}
//渲染数据表格和功能按钮
function tableRender(baseUrl,objCallBack=null,objTipCallBack=null){
    layui.table.render();
    reSizeFrame();
    layui.form.on('submit(search)', function(obj){
        layui.table.reload('frmList',{
            where: obj.field,
            done:function(){
                layui.form.val('frmSearch',obj.field);
            },
            page: {curr:1}
        });
        return false;
    });
    layui.table.on('toolbar(frmList)', function(obj){
        let arrStatus = layui.table.checkStatus(obj.config.id);
        let arrId = [];
        for(let i=0;i<arrStatus.data.length;i++){
            arrId.push(arrStatus.data[i].id);
        }
        if (obj.event !== 'add' && arrId.length < 1){
            return layer.msg('请至少选择一项。',{icon:2});
        }
        let tempToolText = {add:'添加',edit:'编辑',view:'查看'};
        switch(obj.event){
            case 'add':
            case 'edit':
            case 'view':
                layer.open({
                    type: 2
                    ,maxmin: true
                    ,area:['80%', '80%']
                    ,title: tempToolText[obj.event] + document.title
                    ,content: baseUrl+'/'+obj.event.toLowerCase()+'.html' + (obj.event!=='add'?'?id='+arrId.join(','):'')
                });
                break;
            case 'del':
                saveForm(baseUrl+'/del.html',{id:arrId.join(',')},objCallBack,function (json) {
                    if (json.code === 0){
                        layui.table.reload('frmList',{
                            page: {curr:1}
                        });
                    }
                });
                break;
        }
    });
}
//关闭父窗口并刷新列表
function refreshTable(strForm = 'frmList') {
    if (typeof parent === 'object'){
        parent.layui.table.reload(strForm);parent.layer.close(parent.layer.index);
    }else{
        layui.table.reload(strForm);layer.close(parent.layer.index);
    }
}
//提交保存
function saveForm(strUrl,objData,objCallBack=null,objTipCallBack=null){
    layui.jquery.ajax({
        url: strUrl,type: 'post',data: objData,dataType: 'json',
        error: function (obj) {
            if (typeof(objCallBack) === 'function'){
                objCallBack({info:"err:" + JSON.stringify(obj.responseText)});
            }else{
                return layer.msg('请求失败:'+strUrl, {icon: 2,time: 2000});
            }
        },
        success: function(json) {
            if (typeof(objCallBack) === 'function'){
            	objCallBack(json);
            }else{
                layer.msg(json.msg?json.msg:'未知错误', {icon: json.code===0?1:2,time: 2000}, function(){
                    if (typeof(objTipCallBack) === 'function'){
                        objTipCallBack(json);
                    }
                });
            }
        },
    });
}

//select 请求数据创建下拉
function createOption (strUrl ,element, defaultVal, postData,callBack,tip) {
    if (!postData){postData = {valid:1}}
    if (!tip){tip = '顶级分类';}
    layui.jquery.ajax({
        url: strUrl,
        type: 'post',
        data: postData,
        dataType: 'json',
        success: function (json) {
            let data = [], pid = 0;//返回PID，如果有的话
            if (json && json.data && json.data.data) {
                data = json.data.data;
            } else if (json && json.data) {
                data = json.data;
            } else {
                data = json;
            }
            let htmlText = ['<option value="0">' + tip + '</option>'];
            for (let i = 0; i < data.length; i++) {
                if (defaultVal && defaultVal === data[i].id) {
                    htmlText.push("<option value=" + data[i].id + " selected>");
                    if (typeof data[i]['pid'] !== 'undefined') {
                        pid = data[i]['pid'];
                    }
                } else {
                    htmlText.push("<option value=" + data[i].id + ">");
                }
                if (typeof data[i]['pid'] !== 'undefined' && data[i].pid > 0) {
                    htmlText.push('　');
                }
                if (typeof data[i]['name'] !== 'undefined') {
                    htmlText.push(data[i]['name']);
                } else if (typeof data[i]['short_name'] !== 'undefined') {
                    htmlText.push(data[i]['short_name']);
                } else {
                    htmlText.push(data[i]['pName']);
                }
                htmlText.push("</option>");
            }
            layui.jquery(element).append(htmlText.join(""));
            layui.form.render('select'); //这个很重要
            if (typeof callBack === 'function') {
                callBack(pid);
            }
        }
    });
}

//弹出一个选择框
function selectUser(url,title) {
    if (!title){title = '选择';}
    ModalIframe(url,title,'','modal-lg with95');
}
//弹出的选择框，选中内容之后的返回
function selectedToBack(nameId,checkType,fun) {
    var arrValue = [];
    $("input[type='checkbox'].checkId:checked,input[type='radio'].checkId:checked").each(
        function () {
            var id = $(this).val();
            arrValue.push({id:id,'name':$('#'+nameId+id).html()});
        }
    );
    if (checkType == 'radio'){
        arrValue = arrValue.pop();
    }
    if (typeof window.parent.toSelect == 'function'){
        window.parent.toSelect(arrValue,fun);
    }
    closeIframeModal();
}

function setTime(obj,count=10,tip='') { //发送验证码倒计时
    let vTip = '';
    if (count <= 1) {
        obj.removeAttr('disabled');
        vTip = tip?tip:"获取验证码";
        obj.val(vTip);obj.html(vTip);
    } else {
        obj.attr('disabled','disabled');
        vTip = "重新发送(" + count + ")";
        obj.val(vTip);obj.html(vTip);
        setTimeout(function(){setTime(obj,--count,tip);},1000);
    }
}

//获取日期
function getDate(index){
	let newDate = new Date();
	newDate.setDate(newDate.getDate() + index);//官方文档上虽然说setDate参数是1-31,其实是可以设置负数的
    let mon = newDate.getMonth() + 1;
    let day = newDate.getDate();
	return newDate.getFullYear() + "/" + (mon<10?"0"+mon:mon) + "/" +(day<10?"0"+day:day);
}
